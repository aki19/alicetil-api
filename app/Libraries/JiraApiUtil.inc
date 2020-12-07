<?php

namespace App\Libraries;

//JiraProject
define("__JIRA_PROJECT_URL", env("JIRA_PROJECT_URL"));

//JiraAPI
define("__JIRA_SOFTWARE_CLOUD_REST_API", "rest/agile/latest/");
define("__JIRA_LOGIN_ID", env("JIRA_LOGIN_ID"));
define("__JIRA_API_TOKEN", env("JIRA_API_TOKEN"));

class JiraApiUtil {

    private $api_url;

    public function __construct($api_url = __JIRA_PROJECT_URL) {
        $this->api_url = $api_url;
    }

    public function get_sprint_list($initial_option = false) {
        $sprint_list = array();

        $parameters          = array();
        $parameters["state"] = "active,future";

        $headers   = array();
        $headers[] = "Authorization: Basic " . base64_encode(__JIRA_LOGIN_ID . ":" . __JIRA_API_TOKEN);
        list($status_code, $response) = WebAPI::direct_get($this->api_url . __JIRA_SOFTWARE_CLOUD_REST_API . "board/5/sprint", $parameters, true, true, $headers);
        if ($status_code == "200") {
            $datas = $response["values"];
            if (count($datas) > 0) {
                foreach ($datas as $data) {
                    $sprint_list[$data["id"]] = $data["name"];
                }
            }
        }

        if ($initial_option) {
            return array(__PULLDOWN_INITIAL_OPTION) + $sprint_list;
        } else {
            return $sprint_list;
        }
    }

    public function get_task_list($sprint_id) {
        $task_list = array();

        $parameters               = array();
        $parameters["fields"]     = "summary,status,description,parent,subtasks,assignee";
        $parameters["maxResults"] = 100;

        $headers   = array();
        $headers[] = "Authorization: Basic " . base64_encode(__JIRA_LOGIN_ID . ":" . __JIRA_API_TOKEN);
        list($status_code, $response) = WebAPI::direct_get($this->api_url . __JIRA_SOFTWARE_CLOUD_REST_API . "board/5/sprint/{$sprint_id}/issue", $parameters, true, true, $headers);
        if ($status_code == "200") {
            $datas = $response["issues"];
            if (count($datas) > 0) {
                $subtask_list = array();
                foreach ($datas as $data) {
                    $fields = $data["fields"];

                    $issue                = array();
                    $issue["key"]         = $data["key"];
                    $issue["name"]        = $fields["summary"];
                    $issue["description"] = $fields["description"];
                    $issue["status"]      = $fields["status"]["name"];
                    if (isset($fields["assignee"])) {
                        $issue["person"] = $fields["assignee"]["displayName"];
                    } else {
                        $issue["person"] = "未割当";
                    }
                    if (isset($fields["parent"])) {
                        $parent        = $fields["parent"];
                        $parent_fields = $parent["fields"];

                        $issue["parent_key"]  = $parent_fields["key"];
                        $issue["parent_name"] = $parent_fields["summary"];
                    }
                    $task_list[$data["key"]] = $issue;

                    if (isset($fields["subtasks"])) {
                        //子タスク（詳細が直接取得できない）
                        foreach ($fields["subtasks"] as $sub_data) {
                            $sub_fields = $sub_data["fields"];

                            $issue                                        = array();
                            $issue["name"]                                = $sub_fields["summary"];
                            $subtask_list[$data["key"]][$sub_data["key"]] = $issue;
                        }
                    }
                }

                //子タスクの処理
                foreach ($subtask_list as $parent_key => $subtasks) {
                    if (isset($task_list[$parent_key])) {
                        foreach ($subtasks as $sub_key => $subtask) {
                            if (isset($task_list[$sub_key])) {
                                $task_list[$parent_key]["subtasks"][$sub_key] = $task_list[$sub_key];
                                unset($task_list[$sub_key]);
                            }
                        }
                    }
                }
            }
        }

        $sort = array();
        foreach ($task_list as $key => $value) {
            $sort[$key] = isset($value['parent_key']) ? $value['parent_key'] : 0;
        }

        return array_multisort($sort, SORT_ASC, $task_list);
    }

}
