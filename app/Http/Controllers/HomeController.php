<?php

namespace App\Http\Controllers;

use App\Enums\ModifyRequestStatus;
use Illuminate\Http\Request;
use App\Models\Work;
use App\Traits\AppUtility;
use Illuminate\Support\Facades\Auth;
use App\Models\WorkApplication;
use App\Enums\WorkApplicationStatus;
use App\Helpers\WageCalculatorHelper;
use App\Models\City;
use App\Models\FavoriteWork;
use App\Models\HomeReview;
use App\Models\ModifyRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class HomeController extends Controller
{
    use AppUtility;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $filter = [
            'category_id' => $request->category,
            'occupation_id' => $request->occupation,
            'worktime_start_at' => [
                'worktime_start_at',
                '>=',
                $request->form_date
            ],
            'worktime_end_at' => [
                'worktime_end_at',
                '<=',
                $request->to_date
            ],
        ];
        
        if($request->hourly_wage) {
            switch ($request->hourly_wage) {
                case 1:
                    $whereHourlyWage = [
                        ['hourly_wage', '<', 10000]
                    ];
                    break;
                case 2:
                    $whereHourlyWage = [
                        ['hourly_wage', '>=', 10000],
                        ['hourly_wage', '<', 15000]
                    ];
                    break;
                case 3:
                    $whereHourlyWage = [
                        ['hourly_wage', '>=', 15000],
                        ['hourly_wage', '<', 18000]
                    ];
                    break;
                            
                case 4:
                    $whereHourlyWage = [
                        ['hourly_wage', '>=', 18000]
                    ];
                    break;
                            
                default:
                    $whereHourlyWage = [];
                    break;
            };

            $filter = array_merge($filter, $whereHourlyWage);
        }

        $filter = $this->formatSearchFilters($filter);

        $worker_id = Auth::id();

        $workIds = WorkApplication::where('worker_id', $worker_id)
            ->whereNotIn('status', [3])
            ->pluck('work_id');

        $works = Work::where($filter)->whereNotIN('id', $workIds);

        if($request->city){
            $works = $works->whereHas('company', function($query) use($request){
                return $query->where('city', $request->city);
            });
        }

        if($request->work_name){
            $works = $works->search($request->work_name);
        }

        $works = $works->orderBy('id', 'desc')->paginate(10);
        $favoriteWork = FavoriteWork::where('worker_id', $worker_id)->pluck('work_id')->toArray();
        foreach ($works->items() as $key => $work) {
            $work->work_type =  $work->occupation_id ? $this->getValueItemToArray(setting('admin.occupations'), $work->occupation_id) : null;
            $work->is_favorite = in_array($work->id, $favoriteWork);
            $work->format_title = Str::limit($work->title, 40);
        }

        $workTags = $this->getItemStringToArray(setting('admin.tags'));
        $workSkills = $this->getItemStringToArray(setting('admin.skills'));

        $advancedSearch = [
            'search_hourly_wage' => [
                1 => 'D?????i 10,000/gi???',
                2 => '10,000 - 15,000/gi???', 
                3 => '15,000 - 18,000/gi???',
                4 => 'Tr??n 18,000/gi???',
            ],
            'occupations' => $this->getItemStringToArray(setting('admin.occupations')),
            'categories' => $this->getItemStringToArray(setting('admin.categories')),
            'citys' => City::pluck('name', 'id'),
        ];

        $outstandingWorks = Work::getOutstanding();

        return view('homes.works.index')->with(compact('works', 'workTags', 'workSkills', 'advancedSearch', 'outstandingWorks', 'request'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, $slug = null)
    {
        $work = Work::findOrFail($id);
        $qualification_id = $work->work_qualifications->pluck('qualification_id')->toArray();
        $skill_ids = $work->work_skills->pluck('skill_id')->toArray();
        $tag_ids = $work->work_tags->pluck('tag_id')->toArray();

        $work->work_type =  $work->occupation_id ? $this->getValueItemToArray(setting('admin.occupations'), $work->occupation_id) : null;
        $work->category_name =  $work->category_id ? $this->getValueItemToArray(setting('admin.categories'), $work->category_id) : null;
        $work->qualification_name =  $this->getItemToArray($this->getItemStringToArray(setting('admin.qualifications')), $qualification_id);
        $work->skills = $skill_ids ? explode( ', ', $this->getItemToArray($this->getItemStringToArray(setting('admin.skills')), $skill_ids)) : [];
        $work->tags =  $this->getItemToArray($this->getItemStringToArray(setting('admin.tags')), $tag_ids);
        $workApplication = WorkApplication::where('worker_id', Auth::id())->pluck('work_id')->toArray();
        $work->is_applications = in_array($work->id, $workApplication);
        $outstandingWorks = Work::getOutstanding();

        return view('homes.works.show')->with(compact('work', 'outstandingWorks'));
    }

    public function applyWork(Request $request)
    {
        try {
            DB::beginTransaction();
            $worker_id = Auth::id();
            $job = WorkApplication::where('work_id', $request->work_id)
                ->where('worker_id', $worker_id)
                ->whereNotIn('status', [3])
                ->first();
            if ($job) {
                return response()->json([
                    'status' => false,
                    'message' => "B???n ???? n???p h??? s?? cho c??ng vi???c n??y!"
                ]);
            }

            // Create Work Application (apply job)
            $workApplication = WorkApplication::create([
                'work_id' => $request->work_id,
                'worker_id' => $worker_id,
                'status' => WorkApplicationStatus::APPLYING,
                'confirm_yn' => config('const.WorkApplications.CONFIRM_STATUS.NO'),
            ]);

            // save room id firebase
            $workApplication->room_id = $workApplication->id;
            $workApplication->save();

            // // SEnd mail to leader for work
            // $mail = new NotificationEmail($job);
            // $sendEmailJob = new SendNotificationEmail($mail, ['phiasaunucuoi58pm2@gmil.com']);
            // dispatch($sendEmailJob)->afterResponse();
            
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => "B???n ???? n???p h??? s?? cho c??ng vi???c th??nh c??ng!"
            ]);

        } catch (\Exception $e) {
            // Transaction Rollback
            DB::rollBack();
            return response()->json([
                'status' => $e->getCode(),
                'message' => $e->getMessage()
            ]);
        }
    }

    public function unapplyWork(Request $request)
    {
        try {
            DB::beginTransaction();
            $worker_id = Auth::id();
            $job = WorkApplication::where('work_id', $request->work_id)->where('worker_id', $worker_id)->first();
            if (!$job) {
                return response()->json([
                    'status' => false,
                    'message' => "Kh??ng t??m th???y c??ng vi???c n??y"
                ]);
            }

            $job->delete();
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => "Success!"
            ]);

        } catch (\Exception $e) {
            // Transaction Rollback
            DB::rollBack();
            return response()->json([
                'status' => $e->getCode(),
                'message' => $e->getMessage()
            ]);
        }
    }

    public function favorite(Request $request)
    {
        DB::beginTransaction();
        try {
            $worker_id = Auth::id();
            $work = Work::find($request->work_id);
            if (!$work) {
                return response()->json([
                    'status' => false,
                    'message' => "Kh??ng t??m th???y c??ng vi???c ph?? h???p!"
                ]);
            }

            FavoriteWork::where('work_id', $request->work_id)
                ->where('worker_id', $worker_id)
                ->delete();

            FavoriteWork::create([
                'work_id' => $request->work_id,
                'worker_id' => $worker_id,
            ]);

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => "success"
            ]);

        } catch (\Exception $e) {
            // Transaction Rollback
            DB::rollback();
            return response()->json([
                'status' => $e->getCode(),
                'message' => $e->getMessage()
            ]);
        }
    }

    public function unFavorite(Request $request)
    {
        DB::beginTransaction();
        try {
            $worker_id = Auth::id();
            $work = Work::find($request->work_id);
            if (!$work) {
                return response()->json([
                    'status' => false,
                    'message' => "Kh??ng t??m th???y c??ng vi???c ph?? h???p!"
                ]);
            }

            // Create Work Application (apply job)
            FavoriteWork::where('work_id', $request->work_id)
                ->where('worker_id', $worker_id)
                ->delete();

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => "success"
            ]);

        } catch (\Exception $e) {
            // Transaction Rollback
            DB::rollback();
            return response()->json([
                'status' => $e->getCode(),
                'message' => $e->getMessage()
            ]);
        }
    }

    public function confirmWork(Request $request)
    {
        DB::beginTransaction();
        try {
            $worker_id = Auth::id();
            $work = Work::find($request->work_id);
            if (!$work) {
                return response()->json([
                    'status' => false,
                    'message' => "Kh??ng t??m th???y c??ng vi???c ph?? h???p!"
                ]);
            }

            // Create Work Application (apply job)
            $application = WorkApplication::where('work_id', $request->work_id)
                ->where('worker_id', $worker_id)
                ->where('status', WorkApplicationStatus::ASSIGNED)
                ->where('confirm_yn', '=', Config('const.WorkApplications.CONFIRM_STATUS.NO'))
                ->first();

            $application->confirm_yn = config('const.WorkApplications.CONFIRM_STATUS.YES');
            $application->confirmed_at = Carbon::now();
            $application->save();

            // Update work application status to cancel
            $this->removeWorkApply($work);

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => "success"
            ]);

        } catch (\Exception $e) {
            // Transaction Rollback
            DB::rollback();
            return response()->json([
                'status' => $e->getCode(),
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getFavoriteWork()
    {
        $worker_id = Auth::id();
        $outstandingWorks = Work::getOutstanding();
        $workTags = $this->getItemStringToArray(setting('admin.tags'));
        $workSkills = $this->getItemStringToArray(setting('admin.skills'));

        $workApplication = WorkApplication::where('worker_id', $worker_id)->pluck('work_id')->toArray();
        $workIds = FavoriteWork::where('worker_id', $worker_id)->pluck('work_id');
        $works = Work::whereIn('id', $workIds)->orderBy('id', 'desc')->paginate(10);
        
        foreach($works->items() as $work){
            $work->is_application = in_array($work->id, $workApplication);
        }

        return view('homes.works.favorite')->with(compact('works', 'outstandingWorks', 'workTags', 'workSkills'));
    }

    public function getEvaluatingWork(Request $request)
    {
        $worker_id = Auth::id();
        $isReview = $request->isReview ? true : false;

        $reviewWorkIds = [];
        $workApplication = WorkApplication::where('worker_id', $worker_id)
                                ->where('status', WorkApplicationStatus::FINISH);
        
        $reviewWorkIds = HomeReview::where('worker_id', $worker_id)->pluck('work_id')->toArray();
        if($isReview) {
            $workApplication = $workApplication->whereIn('work_id', $reviewWorkIds);
        }
        else {
            $workApplication = $workApplication->whereNotIn('work_id', $reviewWorkIds);
        }

        $workApplication = $workApplication->pluck('work_id')->toArray();

        $works = Work::with('home_review', 'modify_request')->whereIn('works.id', $workApplication)
                    ->orderBy('works.id', 'desc')->paginate(10);

        $outstandingWorks = Work::getOutstanding();
        $workTags = $this->getItemStringToArray(setting('admin.tags'));
        $workSkills = $this->getItemStringToArray(setting('admin.skills'));

        return view('homes.works.evaluating')->with(compact('works', 'outstandingWorks', 'workTags', 'workSkills', 'request'));
    }

    public function workerReviewJob(Request $request)
    {
        $work = Work::find($request->work_id);
        if(!$work){
            return response()->json([
                'status' => false,
                'message' => "Kh??ng t??m th???y c??ng vi???c ph?? h???p!"
            ]);
        }

        DB::beginTransaction();
        try {
            $review = HomeReview::where('worker_id', Auth::id())->where('work_id', $work->id)->first();
            if(!$review){
                $review = new HomeReview();
            }

            $review->fill([
                'worker_id' => Auth::id(),
                'user_id'   => $work->user_id,
                'work_id'   => $work->id,
                'comment'   => $request->comment,
                'good_yn1'  => $request->good_yn1,
                'good_yn2'  => $request->good_yn2,
                'good_yn3'  => $request->good_yn3,
            ])->save();

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'C???m ??n b???n ???? ????nh gi?? c??ng vi???c.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => $e->getCode(),
                'message' => $e->getMessage()
            ], $e->getCode());
        }
    }

    public function workerRequestJob(Request $request)
    {
        $work = Work::find($request->work_id);
        if(!$work){
            return response()->json([
                'status' => false,
                'message' => "Kh??ng t??m th???y c??ng vi???c ph?? h???p!"
            ]);
        }

        DB::beginTransaction();
        try {
            $workerIds = Auth::id();

            $comment = $request->comment;
            $modify_worktime_start_at = $request->modify_worktime_start_at ?? $work->worktime_start_at;
            $modify_worktime_end_at = $request->modify_worktime_end_at ?? $work->worktime_end_at;
            $resttime_minutes = $request->resttime_minutes ?? $work->resttime_minutes ?? 0;

            $wageCalculator = new WageCalculatorHelper(
                $work,
                $modify_worktime_start_at,
                $modify_worktime_end_at,
                $resttime_minutes
            );

            ModifyRequest::create([
                'worker_id' => $workerIds,
                'home_id' => $work->user_id,
                'work_id' => $work->id,
                'comment' => $comment,
                'scheduled_worktime_start_at' => $work->worktime_start_at,
                'scheduled_worktime_end_at' => $work->worktime_end_at,
                'modify_worktime_start_at' => $modify_worktime_start_at,
                'modify_worktime_end_at' => $modify_worktime_end_at,
                'resttime_minutes' => $resttime_minutes,
                'ovetime_percentages' => $work->ovetime_extra_percentages,
                'nighttime_percentages' => $work->night_extra_percentages,
                'ovetime_wage' => $wageCalculator->getOverTimeWage(),
                'nighttime_wage' => $wageCalculator->getNightTimeWage(),
                'base_wage' => $wageCalculator->getBaseWage(),
                'transportation_fee' => $work->transportation_fee,
                'approval_status' => ModifyRequestStatus::NO_APPROVE,
            ]);

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Y??u c???u c???a b???n ???? ???????c ti???p nh???n.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => $e->getCode(),
                'message' => $e->getMessage()
            ], $e->getCode());
        }
    }

    public function workerApplication(Request $request)
    {
        $worker_id = Auth::id();
        $isConfirm = $request->isConfirm ? true : false;

        $outstandingWorks = Work::getOutstanding();
        $workTags = $this->getItemStringToArray(setting('admin.tags'));
        $workSkills = $this->getItemStringToArray(setting('admin.skills'));

        $workApplication = WorkApplication::where('worker_id', $worker_id);

        if($isConfirm) {
            $workIds = $workApplication->where('status', WorkApplicationStatus::ASSIGNED)
                ->where('confirm_yn', config('const.WorkApplications.CONFIRM_STATUS.YES'));
        }
        else {
            $workIds = $workApplication->where('status', WorkApplicationStatus::APPLYING)
                ->orWhere(function($query){
                    $query->where('status', WorkApplicationStatus::ASSIGNED)
                        ->where('confirm_yn', config('const.WorkApplications.CONFIRM_STATUS.NO'));
                });
        }

        $workIds = $workIds->pluck('work_id')->toArray();
        $works = Work::whereIn('id', $workIds)->orderBy('id', 'desc')->paginate(10);

        $favoriteWork = FavoriteWork::where('worker_id', $worker_id)->pluck('work_id')->toArray();
        $workConfirm = WorkApplication::where('worker_id', $worker_id)->where('status', WorkApplicationStatus::ASSIGNED)
            ->where('confirm_yn', config('const.WorkApplications.CONFIRM_STATUS.NO'))
            ->pluck('work_id')->toArray();

        foreach($works->items() as $work){
            $work->is_favorite = in_array($work->id, $favoriteWork);
            $work->is_confirm = in_array($work->id, $workConfirm);
        }

        return view('homes.works.application')->with(compact('works', 'outstandingWorks', 'workTags', 'workSkills', 'request'));
    }

    public function removeWorkApply($workApply)
    {
        $worker = Auth::user();
        $workStart = $workApply->worktime_start_at;
        $workEnd = $workApply->worktime_end_at;

        $workIDs = Work::whereBetween('worktime_start_at', [$workStart, $workEnd])->pluck('id')->toArray();
        $application = WorkApplication::whereIn('work_id', $workIDs)
                ->where('worker_id', $worker->id)
                ->where('status', WorkApplicationStatus::APPLYING)
                ->update(['status' => WorkApplicationStatus::CANCELED]);

        return $application;
    }
}
