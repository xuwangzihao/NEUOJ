<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Storage;
use App\Problem;
use App\User;
use App\Submission;
use App\Executable;
use App\Testcase;

class RESTController extends Controller
{
    public function getConfig(Request $request)
    {
        /*
         * Use fake json config , same as the default config of domjudge
         */
        $json = '{"clar_categories":{ "general":"General issue", "tech":"Technical issue" }, "script_timelimit":30, "script_memory_limit":2097152, "script_filesize_limit":65536, "memory_limit":524288, "output_limit":4096, "process_limit":64, "sourcesize_limit":256, "sourcefiles_limit":100, "timelimit_overshoot":"1s|10%", "verification_required":0, "show_affiliations":1, "show_pending":0, "show_compile":2, "show_sample_output":0, "show_balloons_postfreeze":1, "penalty_time":20, "compile_penalty":1, "results_prio":{ "memory-limit":"99", "output-limit":"99", "run-error":"99", "timelimit":"99", "wrong-answer":"30", "no-output":"10", "correct":"1" }, "results_remap":[ ], "lazy_eval_results":1, "enable_printing":0, "time_format":"%H:%M", "default_compare":"compare", "default_run":"run", "allow_registration":0, "judgehost_warning":30, "judgehost_critical":120, "thumbnail_size":128 }';
        return $json;
    }

    public function postJudgings(Request $request)
    {
        $langsufix = [
                "C" => "c",
                "Java" => "java",
                "C++11" => "cc",
                "C++" => "cpp",
            ];
        $jsonObj = [];
        $submission = Submission::where('judge_status', 0)->first();
        if($submission == NULL)
            return response()->json(NULL);
        $problem = Problem::where('problem_id', $submission->pid)->first();
        $runExecutable = Executable::where('execid', 'run')->first();
        $compileExecutable = Executable::where('execid', $langsufix[$submission->lang])->first();
        $compareExecutable = Executable::where('execid', 'compare')->first();
        /*
         * Make the responce format readable for judgehost
         */
        $jsonObj["submitid"] = $submission->runid;
        $jsonObj["cid"] = 0;
        $jsonObj["teamid"] = $submission->uid;
        $jsonObj["probid"] = $submission->pid;
        $jsonObj["langid"] = $langsufix[$submission->lang];
        $jsonObj["rejudgingid"] = "";
        $jsonObj["maxruntime"] = $problem->time_limit;
        $jsonObj["memlimit"] = $problem->mem_limit;
        $jsonObj["outputlimit"] = 4096;
        $jsonObj["run"] = "run";
        $jsonObj["compare_md5sum"] = $compareExecutable->md5sum;
        $jsonObj["run_md5sum"] = $runExecutable->md5sum;
        $jsonObj["compile_script_md5sum"] = $compileExecutable->md5sum;
        $jsonObj["compare"] = "compare";
        $jsonObj["compare_args"] = "";
        $jsonObj["compile_script"] = $jsonObj["langid"];
        $jsonObj["judgingid"] = $jsonObj["submitid"];
        /*
         * Save current judging_run into database
         * This table only works for domjudge, let GET api/testcases?judgingid=id can get current testcase
         * Now only give one problem one testcase , future will support multitestcases
         */
        //$judgingRun = new JudgingRun;
        //$judgingRun->judgingid =
        Submission::where('runid', $submission->runid)->update(["judge_status" => 1]);
        return response()->json($jsonObj);
    }

    public function getSubmissionFiles(Request $request)
    {
        $input = $request->input();
        $submission = Submission::where('runid', $input['id'])->first();
        $jsonObj[0]["filename"] = $submission->submit_file;
        $content = Storage::get("submissions/".$submission->submit_file);
        $jsonObj[0]["content"] = base64_encode($content);

        return response()->json($jsonObj);
    }

    public function postJudgeHosts(Request $request)
    {
        /*
         * Pretend there is no submissions not judged
         */
        return response()->json(NULL);
    }

    public function getExecutable(Request $request)
    {
        $content = Storage::get("executables/".$request->input('execid').".zip");
        $content = base64_encode($content);
        return response()->json($content);
    }

    public function putJudgings(Request $request, $id)
    {
        $input = $request->input();
        if($input["compile_success"] != "1")
        {
            Submission::where('runid', $id)->update([
                "judge_status" => 3,
                "result" => "Compile Error",
            ]);
        }
    }

    public function getTestcases(Request $request)
    {
        $jsonObj = [];
        $input = $request->input();
        $submission = Submission::where('runid', $input["judgingid"])->where('judge_status', 1)->first();
        if($submission == NULL)
            return response()->json(NULL);
        $testcase = Testcase::where('pid', $submission->pid)->first();
        $jsonObj["testcaseid"] = $testcase->testcase_id;
        $jsonObj["rank"] = 1; //Now only give one problem one testcase so rank is hard-coded
        $jsonObj["probid"] = $testcase->pid;
        $jsonObj["md5sum_input"] = $testcase->md5sum_input;
        $jsonObj["md5sum_output"] = $testcase->md5sum_output;

        return response()->json($jsonObj);
    }

    public function getTestcaseFiles(Request $request)
    {
        $jsonData = "";
        $input = $request->input();
        $testcase = Testcase::where("testcase_id", $input["testcaseid"])->first();
        if(isset($input["input"]))
        {
            $jsonData = Storage::get("testdata/".$testcase->input_file_name);
        }
        else if(isset($input["output"]))
        {
            $jsonData = Storage::get("testdata/".$testcase->output_file_name);
        }
        $jsonData = base64_encode($jsonData);
        return response()->json($jsonData);
    }

    public function postJudgingRuns(Request $request)
    {
        $resultMapping = [
            "wrong-answer" => "Wrong Answer",
            "correct" => "Accepted",
            "no-output" => "Wrong Answer",
            "compiler-error" => "Compile Error",
            "run-error" => "Runtime Error",
            "timelimit" => "Time Limit Exceed",
        ];
        $input = $request->input();
        Submission::where('runid', $input["judgingid"])->update(
            [
                "result" => $resultMapping[$input["runresult"]],
                "exec_time" => $input["runtime"],
                "judge_status" => 3,
                "judgeid" => $input["judgehost"]
            ]
        );
        var_dump($input);
    }
}