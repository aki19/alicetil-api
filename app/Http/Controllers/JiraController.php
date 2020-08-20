<?php

namespace App\Http\Controllers;

use App\Libraries\JiraApiUtil;
use Illuminate\Http\Request;

class JiraController extends Controller {

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        //
    }

    public function get_sprint_list() {
        $util = new JiraApiUtil();
        return response()->json($util->get_sprint_list(), 200);
    }

    public function get_issue_list(Request $request) {
        $json_list = array();

        $util      = new JiraApiUtil();
        $task_list = $util->get_task_list($request->sprint_id);
        foreach ($task_list as $task) {
            $json_list[] = $task;
        }
        return response()->json($json_list, 200);
    }

}
