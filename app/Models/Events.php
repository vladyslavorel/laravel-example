<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Events extends Model
{
    use HasFactory;
    
    protected $table = 'events';
    protected $fillable = ['user_id', 'case_id', 'type_id'];
    
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
    public function find($id) {
      $result = Events::leftJoin('event_types', function($join) {
          $join->on('event_types.id', '=', 'events.type_id');
        })
        ->where('events.id', '=', $id)
        ->where('events.user_id', '=', $_SESSION['user']['id'])
        ->select([
            'events.*',
            'events.at as datetime'
        ])
        ->get();
      return current($result->toArray());
    }
    public function format($data) {
      if(isset($data['at'])) {
        
      }
    }
    public function findByUser($user_id, $offset = 0, $limit = 100) {
      $result = Events::leftJoin('event_types', function($join) {
          $join->on('event_types.id', '=', 'events.type_id');
        })
        ->where('events.user_id', '=', $user_id)
        ->whereNull('events.completed')
        ->offset($offset)
        ->limit($limit)
        ->orderBy('events.at', 'DESC')
        ->select([
            'events.*',
            'events.at as datetime',
            'event_types.name as event_type_name'
        ])
        ->get();
      return $result->toArray();
    }
    public function findByCase($case_id) {
      $result = Events::select()
        ->where('case_id', '=', $case_id)
        ->where('user_id', '=', $_SESSION['user']['id'])
        ->whereNull('completed')
        ->get();
      return $result->toArray();
    }
    public function findByCompanyID($company_id) {
      $result = Events::select()
        ->leftJoin('cases', 'cases.id', '=', 'events.case_id')
        ->where('cases.company_id', '=', $company_id)
        ->get();
      return $result->toArray();
    }
    public function findAllByCase($case_id) {
      $result = Events::select([
            'events.*',
            DB::raw('CONCAT(u.first_name, " ", u.last_name) as sales_rep_name'),
            'event_types.name as event_type_name'
          ])
        ->leftJoin('users as u', 'u.id', '=', 'events.user_id')
        ->leftJoin('event_types', 'event_types.id', '=', 'events.type_id')
        ->where('events.case_id', '=', $case_id)
        ->whereNull('events.completed')
        ->get();
      return $result->toArray();
    }
    public function findByDate($start_date, $end_date = null) {
      
    }
    public function findByUserAndDate($start_date, $end_date = null, $user_id) {
      
    }
    
    
    
    
    
    
    
    public function availabilityAlgorithm($events, $start, $end, $duration, $user_id) {
        //pull these values from database
        //$businessHours = Model_CalendarOptions::getHoursByUser($user_id);
        //$companyHolidays = Model_CalendarOptions::getHolidaysByCompany(Model_User::findCompanyByUserId($user_id));
        
        $businessHours = array(
          'sun'=>1,
          'mon'=>2,
          'tues'=>3,
          'wed'=>4,
          'thur'=>5,
          'fri'=>6,
          'sat'=>7,
          'tuesStart'=>1, 'tuesEnd'=>9,
          'wedStart'=>1, 'wedEnd'=>9,
          'thurStart'=>1, 'thurEnd'=>9,
          'friStart'=>1, 'friEnd'=>9,
          'satStart'=>1, 'satEnd'=>9,
        );
        $companyHolidays = array("NY" => "", "MLK" => "", "president", "easter"=>"closed", "memorial", "fourth", "labor", "columbus", "veterans", "thanksgiving", "christmasEve", "christmas", "NYE");
        
        $frequency = '15 minutes';

        $time_slots = array();
        $time = self::roundTime($start, 15);
        while($time < $end){

            if(self::canSchedule($time, $businessHours, $companyHolidays, $duration)){
                if (count($events) != 0) {

                    for ($i = 0; $i < count($events); $i++) {

                        //check if the event starting at $time would intersect any other events
                        if((($events[$i]['start'] <= $time) && ($time < $events[$i]['end'])) ||
                            (($events[$i]['start'] <= self::addTime($duration, strtotime($time)))
                                && (self::addTime($duration, strtotime($time)) < $events[$i]['end']))) {

                            $i = count($events);

                        }else if($i == count($events) - 1) {

                            array_push($time_slots, date("D M jS Y h:i a", strtotime($time)));

                        }
                    }

                }else{
                    array_push($time_slots, date("D M jS Y h:i a", strtotime($time)));
                }


                $time = self::addTime($frequency, strtotime($time));

            }
        }

        //return available time_slots as an array
        return $time_slots;
    }
    
    private static function addTime ($time1, $time2){
        return date("Y-m-d H:i:s", strtotime("+" . $time1, $time2));
    }

    private static function appendTime($date, $time){
        return date ("Y-m-d H:i:s", strtotime(date("Y-m-d", $date) . " " . $time));
    }

    private static function roundTime($time, $precision) {
        $time = strtotime($time);
        $precision = 60 * $precision;
        return date("Y-m-d H:i:s", ceil($time / $precision) * $precision);
    }
    
    private static function canSchedule(&$time, $businessHours, $companyHolidays, $duration=0){

        $days = array('sun', 'mon', 'tues', 'wed', 'thur', 'fri', 'sat');
        $weekday = date('w', strtotime($time));
        $canSchedule = true;
        if(1 == $businessHours[$days[$weekday]]){
            $canSchedule = false;
            $time = date("Y-m-d 00:00:00", strtotime('+1 Day', strtotime($time)));


        }else if(self::addTime($duration, strtotime($time)) > self::appendTime(strtotime($time), $businessHours[$days[$weekday] . 'End'])){
            $canSchedule = false;
            $time = date("Y-m-d 00:00:00", strtotime('+1 Day', strtotime($time)));

        }else if($time < self::appendTime(strtotime($time), $businessHours[$days[$weekday] . 'Start'])){
            $canSchedule = false;
            $time = self::roundTime(self::appendTime(strtotime($time), $businessHours[$days[$weekday] . 'Start']), 15);

        }else if(self::intersectsCompanyHoliday($time, $companyHolidays)){
            $canSchedule = false;
            $time = date("Y-m-d 00:00:00", strtotime('+1 Day', strtotime($time)));

        }
        return $canSchedule;

    }
    private static function intersectsCompanyHoliday($time, $companyHolidays){
        $names = array("NY", "MLK", "president", "easter", "memorial", "fourth", "labor", "columbus", "veterans", "thanksgiving", "christmasEve", "christmas", "NYE");
        $holidays = self::getHolidays($time);
        $conflict = false;
        for($i = 0; ($i < count($holidays)) && !$conflict; $i++){
            if(isset($companyHolidays[$names[$i]])) {
                if (("closed" == $companyHolidays[$names[$i]]) && ($holidays[$i] == date('Y-m-d', strtotime($time)))) {
                    $conflict = true;
                }
            }
        }
        return $conflict;
    }
    
    private static function getHolidays($time){
        //finds dates of the federal holidays
        $holidays = array();
        $year = date('Y', strtotime($time)); //$time's year

        array_push($holidays, $year . '-01-01'); //New Year's day
        array_push($holidays, date('Y-m-d', strtotime("january $year third monday"))); //Martin Luthor King Jr. Day
        array_push($holidays, date('Y-m-d', strtotime("february $year third monday"))); //Presidents Day
        array_push($holidays, date('Y-m-d', easter_date($year))); // Easter

        //Memorial Day is slightly more complicated
        $MDay = date('Y-m-d', strtotime("may $year first monday")); // Memorial Day
        //("may $year last monday") will give you the last monday of may 1967
        $explodedMDay = explode("-",$MDay);
        $month = $explodedMDay[1];
        $day = $explodedMDay[2];

        while($day <= 31){
            $day = $day + 7;
        }
        if($day > 31){
            $day = $day - 7;
        }

        $MDay = $year.'-'.$month.'-'.$day;
        array_push($holidays, $MDay);

        array_push($holidays, $year . '-07-04'); //Independence Day
        array_push($holidays, date('Y-m-d', strtotime("september $year first monday"))); //Labor Day
        array_push($holidays, date('Y-m-d', strtotime("october $year third monday"))); //Columbus Day
        array_push($holidays, $year . '-11-11'); //Veteran's Day

        //Thanksgiving is slightly more complicated
        $thanksgiving = date('Y-m-d', strtotime("november $year first thursday")); // Thanksgiving
        //("november $year last thursday") will give you the last thursday of november 1967
        $explodedThanksgiving = explode("-",$thanksgiving);
        $month = $explodedThanksgiving[1];
        $day = $explodedThanksgiving[2];

        while($day <= 30){
            $day = $day + 7;
        }
        if($day > 30){
            //watch out for the days in the month November only have 30
            $day = $day - 7;
        }

        $thanksgiving = $year.'-'.$month.'-'.$day;
        array_push($holidays, $thanksgiving);

        array_push($holidays, $year . '-12-24'); //Christmas Eve
        array_push($holidays, $year . '-12-25'); //Christmas
        array_push($holidays, $year . '-12-31'); //New Year's Eve

        return $holidays;
    }
}
