<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\TransactionRepository;

class QueueController extends Controller
{
    private $transactionRepo;

    public function __construct(TransactionRepository $transactionRepo){
        $this->transactionRepo = $transactionRepo;
    }
    
    public function push(Request $request){
        $user = $request->has('uuid') ? \App\User::where('uuid', $request->uuid)->first() : auth('api')->user();
        if(!$user) return response()->json(['status' => false, 'message' => 'Cannot find user.']);
        return $this->transactionRepo->push($request, $user);
    }
    
    public function retrieveWaitingTime($id){
        return response()->json([
            'waiting_time' => $this->transactionRepo->generateWaitingTimeFor($this->transactionRepo->findQueueById($id)),
        ]);
    }

    public function getQueues(Request $request){
        $user = auth('api')->user();

        $serviceIds = $user->myServersServices->pluck('services')->flatten()->unique('id')->pluck('id');
        $queues = \App\Queue::whereIn('service_id', $serviceIds)
                            ->whereDate('created_at', \Carbon\Carbon::today())
                            ->whereIn('status', ['queueing', 'skipped'])
                            ->orderBy('status')
                            ->get();

        $currentlyServing = \App\Queue::where('server_id', $request->server_id)
                            ->whereDate('created_at', \Carbon\Carbon::today())
                            ->where('status', 'serving')
                            ->limit(1)
                            ->get()
                            ->first();
        
        return response()->json([
            'result' => $queues,
            'currently_serving' => $currentlyServing,
        ]);
    }
    
    public function serveNext(Request $request){
        $user = auth('api')->user();
        
        $serviceIds = $user->myServersServices->pluck('services')->flatten()->unique('id')->pluck('id');
        $server = \App\Server::find($request->server_id);
        $currentlyServing = \App\Queue::where('server_id', $server->id)
                            ->whereDate('created_at', \Carbon\Carbon::today())
                            ->where('status', 'serving')
                            ->limit(1)
                            ->get()
                            ->first();
        
        if($currentlyServing){
            if($request->action == 'skip') {
                $server->skippedQueues()->attach($currentlyServing);
            }
            $currentlyServing->status = $request->action == 'skip' ? 'skipped' : 'served';
            $currentlyServing->updated_at = \Carbon\Carbon::now();
            $currentlyServing->save();
            $nextStep = null;
            if($currentlyServing->transaction->flow()->exists() && $request->action != 'skip'){
                $nextStep = $currentlyServing->transaction->flow->steps()->where('status', 'queueing')->first();
                if($nextStep){
                    $this->transactionRepo->createQueueFor($currentlyServing->transaction, [
                        'department_id' => $nextStep->department->id,
                        'service_id' => $nextStep->service->id,
                    ]);
                    $nextStep->pivot->status = 'processing';
                    $nextStep->pivot->save();
                }else {
                    $currentlyServing->transaction->status = 'completed';
                    $currentlyServing->transaction->save();

                    $currentlyServing->transaction->flow->status = 'completed';
                    $currentlyServing->transaction->flow->save();
                }
            }
        }

        $queue = \App\Queue::whereIn('service_id', $serviceIds)
                            ->whereDate('created_at', \Carbon\Carbon::today())
                            ->whereIn('status', ['queueing', 'skipped'])
                            ->orderBy('updated_at')
                            ->limit(1)
                            ->get()
                            ->first();

        $updated = false;
        if($queue){
            $queue->status = 'serving';
            $queue->server_id = $request->server_id;
            $updated = $queue->save();

            \Twilio::message($queue->transaction->user->mobile_no, 'Your Number(' . $queue->priority_number . ') is being currently served in ' . $server->name);
        }

        return response()->json([
            'status' => $queue ? $updated : true,
            'currently_serving' => $queue,
        ]);
    }
    
    public function serviceQueues(Request $request){
        $department = \App\Department::find($request->department_id);
        $queues = $department->servers()
                            ->with([
                                'queues' => function($q){
                                    $q->whereDate('queues.created_at', \Carbon\Carbon::now())
                                    ->where('queues.status', 'serving')
                                    ->limit(1)
                                    ->first();
                                }
                            ])
                            ->get();
        return response()->json([
            'result' => $queues,
        ]);
    }
    
    public function find(Request $request){
        $transaction = $this->transactionRepo->findById($request->id);
        return response()->json($transaction->queues()->with('service', 'service.servers')->get());
    }
    
}
