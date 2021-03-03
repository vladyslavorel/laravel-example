<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Payments extends Model
{
    use HasFactory;
    
    protected $table = 'payments';
    
		public function __construct() {
      DB::enableQueryLog();
    }
    public function logger($last = true) {
      $queries = DB::getQueryLog();
      if($last) {
        $queries = array(end($queries));
      }
      $formattedQueries = [];
      if($queries[0]):
        foreach ($queries as $query) :
            $prep = $query['query'];

            foreach ($query['bindings'] as $binding) :

                if (is_bool($binding)) {
                    $val = $binding === true ? 'TRUE' : 'FALSE';
                } else if (is_numeric($binding)) {
                    $val = $binding;
                } else {
                    $val = "'$binding'";
                }

                $prep = preg_replace("#\?#", $val, $prep, 1);
            endforeach;
            $formattedQueries[] = $prep;
        endforeach;
      endif;
      if($last && $formattedQueries) {
        $formattedQueries = $formattedQueries[0];
      }
      return $formattedQueries;
    }
		
    public function find($case_id, $id) {
      $result = Payment::where('case_id', $case_id)
                    ->where('active','!=', 0)
                    ->where('id', $id)->getArray();
      return current($result->toArray());
    }
    public function findAll() {
      return array();
    }
    public function findByCaseID($case_id){
      $result = Payment::where('case_id', $case_id)
            ->where('active','!=',0)
            ->getArray();
            return $result;
    }
    static function findTotalByFilter($filter, $group_by = 'day', $solution='count'){

      switch($solution){
          case 'sum':
              $solution = 'sum(p.amount) as total';
              break;
          case 'count':
              $solution = 'count(DISTINCT p.id) as total';
              break;
          case 'filecount':
              $solution = 'count(p.case_id) as total';
              break;
      }

      // $query = \DB::select(
      //     DB::expr($solution),
      //     DB::expr('EXTRACT(YEAR FROM p.created) as year'),
      //     DB::expr('EXTRACT(MONTH FROM p.created) as month'),
      //     DB::expr('EXTRACT(DAY FROM p.created) as day')
      // )
      //     ->from(array('payments', 'p'))
      //     ->join(array('cases','c'), 'LEFT')->on('c.id', '=', 'p.case_id')
      //     ->join(array('case_contact','cc'), 'LEFT')->on('cc.case_id', '=', 'c.id')
      //     ->join(array('case_statuses','cs'), 'LEFT')->on('cs.case_id', '=', 'c.id');
      
      // mmm
      $query = Payment::from('payment as p')
      ->selectRaw('YEAR(p.created) as year, MONTH(p.created) as month, DAY(p.created) as day')
      ->leftJoin('cases as c', 'c.id', '=', 'p.case_id')
      ->leftJoin('case_contact as cc', 'cc.case_id', '=', 'c.id')
      ->leftJoin('case_statuses as cs', 'cs.case_id', '=', 'c.id');    

      if(isset($filter['amount']) && !empty($filter['amount'])){
          //???
          $query->where('p.amount',$filter['amount_operator'],$filter['amount']);
      }

      if(isset($filter['status']) && !empty($filter['status'])){
          $query->where('p.status_id','=',$filter['status']);
      }

      if(isset($filter['company_id']) && !empty($filter['company_id'])){
          $query->where('p.parent_id','=',$filter['company_id']);
      }

      if (!empty($filter['dates']) && $filter['dates'] != 'all_time') {

          $date_field = 'p.created';

          /* Fix for 2038 + */
          //$d = new DateTime( '2040-11-23' );
          // echo $d->format( 'Y-m-t' );

          if ($filter['dates'] == 'day') {
              // $query->where($date_field, 'between', array(
              //     date('Y-m-d 00:00:00', strtotime($filter['date'])),
              //     date('Y-m-d 23:59:59', strtotime($filter['date']))
              // ));

              //mmm
              $query->whereBetween($date_field, array(
                  date('Y-m-d 00:00:00', strtotime($filter['date'])),
                  date('Y-m-d 23:59:59', strtotime($filterp['date']))
              ));
          } elseif ($filter['dates'] == 'month') {
              // $query->where($date_field, 'between', array(
              //     date('Y-m-', strtotime($filter['date'])) . '01 00:00:00',
              //     date('Y-m-t' , strtotime($filter['date'])). ' 23:59:59'
              // ));
              //mmm
              $query->whereBetween($date_field, array(
                  date('Y-m-', strtotime($filter['date'])) . '01 00:00:00',
                  date('Y-m-t' , strtotime($filter['date'])). ' 23:59:59'
              ));
          } elseif ($filter['dates'] == 'year') {
              // $query->where($date_field, 'between', array(
              //     date('Y-', strtotime($filter['date'])) . '01-01 00:00:00',
              //     date('Y-' , strtotime($filter['date'])). '12-31 23:59:59'
              // ));
              //mmm
              $query->whereBetween($date_field, array(
                  date('Y-', strtotime($filter['date'])) . '01-01 00:00:00',
                  date('Y-' , strtotime($filter['date'])). '12-31 23:59:59'
              ));
          }elseif ($filter['dates'] == 'custom') {
              // $query->where($date_field, 'between', array(
              //     date('Y-m-d', strtotime($filter['start_date'])). ' 00:00:00', 
              //     date('Y-m-d', strtotime($filter['end_date'])) . ' 23:59:59'
              // ));
              //mmm
              $query->whereBetween($date_field, array(
                  date('Y-m-d', strtotime($filter['start_date'])). ' 00:00:00', 
                  date('Y-m-d', strtotime($filter['end_date'])) . ' 23:59:59'
              ));
          }
      }

      $query = \Model_System_Access::queryAccess($query);

      if($group_by) {
          // $query->group_by($group_by);
          //mmm
          $query->groupBy($group_by);
      }

      // $results = current($query->execute()->as_array());
      //mmm
      $results = current($query->getArray());

      return $results;
  }


  static function update($id, $data){

      if(isset($data['date_due'])){
          $data['date_due'] = date('Y-m-d H:i:s', strtotime($data['date_due']));
      }

      // DB::update('payments')->set($data)->where('id', '=', $id)->execute();
      //mmm
      $db = Payment::find($id);
      $db->date_due = date('Y-m-d H:i:s', strtotime($data['date_due']));
      $db->save();
  }

  static function delete($id){
      // DB::update('payments')->set(array('active'=>0))->where('id', '=', $id)->execute();
      //mmm
      $db = Payment::find($id);
      $db->active = 0;
      $db->save();
  }


  static function findNextPayment($case_id){
      // $result = \DB::select()->from('payment_schedules')
      //     ->where('case_id', '=', $case_id)
      //     ->where('status', 'in', array('pending','processing','hold'))
      //     ->where('active', '!=', 0)
      //     ->order_by('date_due')->limit(1)->execute();
      // return current($result->as_array());

      //mmm
      $result = Payment_schedules::where('case_id', $case_id)
              ->whereIn('status', ['pending','processing','hold'])
              ->where('active', 1)
              ->orderBy('date_due', 'Asc')
              ->limit(1)
              ->get();
  }


  static function getTotalPayments($case_id, $all_scheduled = false){

      // $query = \DB::select('id', 'amount')->from('payments')
      // ->where('case_id', '=', $case_id)->where('active', '!=', 0);
      //mmm
      $query = Payment::select('id', 'amount')
              ->where('case_id', $case_id)
              ->where('active', '!=', '0');

      if($all_scheduled){
          // $query->where('status_id','not in',array(5,3)); // NSF , Paid
          //mmm
          $query->whereNotIn('status_id', array(5,3));
      }else{
          // $query->where('status_id','=', 3); // Paid
          //mmm
          $query->where('status_id', 3);
      }

      // $result = $query->execute();
      //mmm
      $result = $query->get();

      $payments = array();
      foreach($result->as_array() as $p){
          $payments[] = $p['amount'];
      }

      return array_sum($payments);

  }


  static function process($case_id, $data){

      $payment = array(
          'status' => $data['status'],
          'amount_received' => $data['amount_received'],
          'received_by' => $_SESSION['user']['id'],
          'date_received' => date('Y-m-d H:i:s'),
          'received_note' => $data['received_note'],
          'updated' => date('Y-m-d H:i:s'),
          'updated_by' => $_SESSION['user']['id']
      );


      if(!isset($data['id']) || empty($data['id'])){

          $add = array(
              'case_id' => $case_id,
              'amount' => $data['amount_received'],
              'date_due' => date('Y-m-d'),
              'created' => date('Y-m-d H:i:s'),
              'created_by' => $_SESSION['user']['id']
          );

          $new = array_merge($payment, $add);

          DB::insert('payments')->set($new)->execute();

      }else{
          DB::update('payments')->set($payment)->where('id', '=', $data['id'])->where('case_id', '=', $case_id)->execute();
      }

  }

  static function generatePaymentPlan($data){

      $minimum_payment_amount = 25;

      $payments_made = self::getTotalPayments($data['case_id']);
      $payments_scheduled = \Accounting\Model_Payment_Schedules::getAllSchedulesSum($data['case_id'], true);
      $total_payments_due = Model_Invoice::getTotalByCaseID($data['case_id']) - ($payments_made + $payments_scheduled); // revisit
      /*if($total_payments_due == 0){
          \Notification\Notify::error('Payments are already scheduled for the entire balance due');
      }*/

      if($data['generate_by'] == 'number'){
          $payments_amount = $total_payments_due / $data['number_payments'];
      }else{
          $payments_amount = $data['payment_amount'];
          $data['number_payments'] = ceil($total_payments_due / $payments_amount);
      }

      $payments = array();

      $last_payment = false;
      $sch_payments = array();
      $last_pending_payment_date = \Accounting\Model_Payment_Schedules::getLastPendingDate($data['case_id']);
      $start_date = (empty($last_pending_payment_date)?$data['start_date']:$last_pending_payment_date);
      $date_due = date('Y-m-d', strtotime($start_date));

      for($i=1;$i<=$data['number_payments'];$i++){

          if($i>1 || !empty($last_pending_payment_date)){
              $date_due = date('Y-m-d', strtotime('+'.$data['payment_frequency'], strtotime($date_due)));
          }

          $next_period_due = $total_payments_due-(array_sum($sch_payments)+$payments_amount);

          if($next_period_due < $minimum_payment_amount){
              $last_payment = true;
          }elseif($i==$data['number_payments']){
              $last_payment = true;
          }

          $payment = array(
              'case_id' => $data['case_id'],
              'amount' => ($last_payment?$total_payments_due-array_sum($sch_payments):$payments_amount),
              'date_due' => $date_due,
              'created' => date('Y-m-d H:i:s'),
              'created_by' => $_SESSION['user']['id'],
              'updated' => date('Y-m-d H:i:s'),
              'updated_by' => $_SESSION['user']['id']
          );

          DB::insert('payment_schedules')->set($payment)->execute();

          if($last_payment){
              return;
          }

          $sch_payments[] = $payments_amount;
      }

  }

  static function getPaymentPlanSummary($case_id){

      // $payments_res = \DB::select('amount')->from('payment_schedules')
          // ->where('case_id', '=', $case_id)->order_by('date_due')->execute();
      // $payments = $payments_res->as_array();
      //mmm
      $payments = Payment_schedule::select('amount')
      ->where('case_id', $case_id)
      ->orderBy('date_due', 'Asc')
      ->getArray();

      // $result = \DB::select(DB::expr('MIN(date_due) as pay_start_date'), 
      // DB::expr('MAX(date_due) as pay_end_date'), DB::expr('COUNT(id) as pay_payments'))
      //     ->from('payments')
      //     ->where('case_id', '=', $case_id)
      //     ->group_by('case_id')
      //     ->execute();
      // $plan = current($result->as_array());

      //mmm
      $result = Payment::selectRaw('MIN(date_due) as pay_start_date, MAX(date_due) as pay_end_date,
              COUNT(id) as pay_payments')
              ->where('case_id', $case_id)
              ->groupBy('case_id')
              ->getArray();

      $i = 1;
      foreach($payments as $p){
          $plan['pay_payment_amount'.$i] = $p['amount'];
          $i++;
      }

      $plan['pay_total_payments'] = count($payments);

      if($plan['pay_payments'] == 0){
          $plan['pay_schedule'] = null;
          return $plan;
      }

      $start_date = new DateTime($plan['pay_start_date']);
      $end_date = new DateTime($plan['pay_end_date']);

      if(!empty($plan['pay_start_date'])){
          $plan['pay_start_date'] = date('m/d/Y', strtotime($plan['pay_start_date']));
      }

      if(!empty($plan['pay_end_date'])){
          $plan['pay_end_date'] = date('m/d/Y', strtotime($plan['pay_end_date']));
      }

      $month_test = clone $start_date;
      $month_test->modify('+'.($plan['pay_payments']-1).' Months');

      $seconds = $end_date->getTimestamp() - $start_date->getTimestamp();
      $weeks = round($seconds / 60 / 60 / 24 / 7)+1;

      if($month_test->format('Y-m-d') == $end_date->format('Y-m-d')){
          $plan['pay_schedule'] = 'Monthly';
      }elseif($plan['pay_payments'] == $weeks){
          $plan['pay_schedule'] = 'Weekly';
      }else{
          $plan['pay_schedule'] = 'Bimonthly';
      }

      return $plan;
  }

  /** 
   *  Static method that delivers all
   *  payments with status = Pending
   *  @return mix     matrix of records
   **/
  static function getProcessingPayments($processor=false){

      // $query = \DB::select('ps.*','c.company_id')
      //     ->from(array('payment_schedules','ps'))
      //     ->join(array('cases','c'),'LEFT')->on('c.id','=','ps.case_id')
      //     ->where('ps.status_id', '=', 1)
      //     ->where('ps.transaction_id',null,\DB::expr('IS NOT NULL'))
      //     //->and_where('ps.process_date','<', date('Y-m-d', strtotime('-3 days')))// Should be 1
      //     ->order_by('ps.updated', 'DESC');
      //mmm ???
      $query = Payment_schedule::from('payment_schedules as ps')
              ->select('ps.*', 'c.company_id')
              ->where('ps.status_id', 1)
              ->where('ps.transaction_id', null)
              ->orderBy('ps.updated', 'Desc');

      if($processor){
          // $query->and_where('ps.processor','=',$processor);
          //mmm
          $query->orWhere('ps.processor', $processor);
      }

      // $result = $query->execute()->as_array();
      //mmm
      $result = $query->get();

      return $result;
  }


  // Ready to Purchase
  static function findAccountsPaidDownIds(){

      // $query = \DB::query("select p.case_id
      //                     from payments p
      //                     left join case_statuses cs on cs.case_id = p.case_id
      //                     where p.status_id = 3
      //                     /*and cs.submission_ready IS NULL*/
      //                     and p.active = 1
      //                     and p.processor = 'EQUITABLE'
      //                     and cs.status_id IN (452,453)");
      // $result = $query->execute()->as_array();
      //mmm
      $result = Payment::from('payments as p')
                      ->select('p.case_id')
                      ->leftJoin('case_statuses as cs', 'cs.case_id','p.case_id')
                      ->where('p.status_id', 3)
                      ->where('p.active', 1)
                      ->where('p.processor', 'EQUITABLE')
                      ->whereIn('cs.status_id',[452, 453])
                      ->getArray();
      $ids = array();
      if($result){
          foreach($result as $file){
              $ids[] = $file['case_id'];
          }
      }
      return $ids;
  }


  static function findPendingDepositTrustReady(){

      // Status : Pending Deposit, Schedule Type : Down Payment, Amount : 150
      // $query = \DB::query("select p.case_id
      //             from payments p
      //             left join case_statuses cs on cs.case_id = p.case_id
      //             left join payment_schedules ps on ps.id = p.schedule_id
      //             where p.status_id = 3
      //             and cs.account_type_id IN (3,2)
      //             and ps.type_id IN (12,14)
      //             and p.amount > 99
      //             and cs.status_id = 480
      //             and p.active = 1
      //             group by p.case_id");

      // $result = $query->execute()->as_array();
      //mmm
      $result = Payment::from('payments as p')
              ->select('p.case_id')
              ->leftJoin('case_statuses as cs', 'cs.case_id', 'p.case_id')
              ->leftJoin('payment_schedules as ps', 'ps.id', 'p.schedule_id')
              ->where('p.status_id', 3)
              ->whereIn('cs.account_type_id', [3,2])
              ->whereIn('ps.type_id', [12,14])
              ->where('p.amount','>', 99)
              ->where('cs.status_id', 480)
              ->where('p.active', 1)
              ->groupBy('p.case_id')
              ->getArray();
      $ids = array();
      if($result){
          foreach($result as $file){
              $ids[] = $file['case_id'];
          }
      }
      return $ids;
  }

  static function findPendingDepositReady(){

      // Status : Pending Deposit, Schedule Type : Down Payment, Amount : 150
      // $query = \DB::query("select p.case_id
      //             from payments p
      //             left join case_statuses cs on cs.case_id = p.case_id
      //             left join payment_schedules ps on ps.id = p.schedule_id
      //             where p.status_id = 3
      //             and cs.account_type_id IN (1,7)
      //             and ps.type_id IN (12,14)
      //             and p.amount > 100
      //             and cs.status_id = 480
      //             and p.active = 1
      //             group by p.case_id");

      // $result = $query->execute()->as_array();
      // mmm
      $result = Payment::from('payments as p')
              ->select('p.case_id')
              ->leftJoin('case_statuses as cs', 'cs.case_id', 'p.case_id')
              ->leftJoin('payment_schedules as ps','ps.id', 'p.schedule_id')
              ->where('p.status_id', 3)
              ->whereIn('cs.account_type_id', [1, 7])
              ->whereIn('ps.type_id', [12, 14])
              ->where('p.amount', '>', 100)
              ->where('cs.status_id', 480)
              ->where('p.active', 1)
              ->groupBy('p.case_id')
              ->getArray();
      $ids = array();
      if($result){
          foreach($result as $file){
              $ids[] = $file['case_id'];
          }
      }
      return $ids;
  }
}
