<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Events;

class EventsController extends Controller
{
    /**
     * Constructor.
     *
     */
    public function __construct()
    {
        $_SESSION['user']['id'] = 1;
    }
    
    public function findById(Request $request)
    {
        $event_id = $request->event_id;
        $events = new Events();
        $data = $events->find($event_id);
        $query = $events->logger('last');
        return ['status' => 'success', 'data' => $data, 'query' => $query];
    }
    
    public function findByUser(Request $request)
    {
        $user_id = $request->user_id;
        $offset = $request->offset;
        $limit = $request->limit;
        $events = new Events();
        $data = $events->findByUser($user_id, $offset, $limit);
        $query = $events->logger('last');
        return ['status' => 'success', 'data' => $data, 'query' => $query];
    }
    
    public function findByCase(Request $request)
    {
        $case_id = $request->case_id;
        $events = new Events();
        $data = $events->findByCase($case_id);
        $query = $events->logger('last');
        return ['status' => 'success', 'data' => $data, 'query' => $query];
    }
    
    public function findByCompanyID(Request $request)
    {
        $company_id = $request->company_id;
        $events = new Events();
        $data = $events->findByCompanyID($company_id);
        $query = $events->logger('last');
        return ['status' => 'success', 'data' => $data, 'query' => $query];
    }
    
    public function findAllByCase(Request $request)
    {
        $case_id = $request->case_id;
        $events = new Events();
        $data = $events->findAllByCase($case_id);
        $query = $events->logger('last');
        return ['status' => 'success', 'data' => $data, 'query' => $query];
    }
    
    
    
    
    
    
    
    public function testAPI(Request $request)
    {
        $message = "";
        $events = new Events();
        //
        $message = "availabilityAlgorithm test";
        $event_data = array(
          array(
            "start"=> "2021-04-03 07:40:00",
            "end"=> "2021-04-03 07:40:00"
          )
        );
        $start = "6 April 2021";
        $end = "2021-04-10 07:40:00";
        $duration = 172800;
        $user_id = 1;
        
        $data = $events->availabilityAlgorithm($event_data, $start, $end, $duration, $user_id);
        //exit;
        $query = $events->logger('last');
        return ['status' => 'success', 'data' => $data, 'query' => $query, 'message' => $message];
    }
}
