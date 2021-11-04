<?php

namespace App\Libraries;

//JiraProject
define("__JIRA_PROJECT_URL", env("JIRA_PROJECT_URL"));

//JiraAPI
define("__JIRA_SOFTWARE_CLOUD_REST_API", "rest/agile/latest/");
define("__JIRA_CLOUD_PLATFORM_REST_API", "rest/api/latest/");
define("__JIRA_LOGIN_ID", env("JIRA_LOGIN_ID"));
define("__JIRA_API_TOKEN", env("JIRA_API_TOKEN"));
define("__JIRA_CBTS_OLD_BOARD_ID", "1");
define("__JIRA_CBTS_BOARD_ID", "5");

class JiraApiUtil {

    private $api_url;

    public function __construct($api_url = __JIRA_PROJECT_URL) {
        $this->api_url = $api_url;
    }

    public function get_sprint_list($board_id = __JIRA_CBTS_BOARD_ID, $state = "active,future") {
        $sprint_list = array();

        $parameters          = array();
        $parameters["state"] = $state;

        $headers   = array();
        $headers[] = "Authorization: Basic " . base64_encode(__JIRA_LOGIN_ID . ":" . __JIRA_API_TOKEN);
        list($status_code, $response) = WebAPI::direct_get($this->api_url . __JIRA_SOFTWARE_CLOUD_REST_API . "board/" . $board_id . "/sprint", $parameters, true, true, $headers);
        if ($status_code == "200") {
            $datas = $response["values"];
            if (count($datas) > 0) {
                foreach ($datas as $data) {
                    if (isset($data["originBoardId"]) && $data["originBoardId"] == $board_id) {
                        if ($data["name"] != "タスク移行しました。") {
                            $data_properties            = array();
                            $data_properties["name"]    = $data["name"];
                            $data_properties["endDate"] = date("Y-m-d", strtotime($data["endDate"]));
                            $data_properties["state"]   = $data["state"];
                            $sprint_list[$data["id"]]   = $data_properties;
                        }
                    }
                }
            }
        }

        return $sprint_list;
    }

    public function get_sprint_issue_list($sprint_id) {
        $task_list = array();

        $parameters               = array();
        $parameters["fields"]     = "summary,status,description,parent,subtasks,assignee";
        $parameters["maxResults"] = 100;

        $headers   = array();
        $headers[] = "Authorization: Basic " . base64_encode(__JIRA_LOGIN_ID . ":" . __JIRA_API_TOKEN);
        list($status_code, $response) = WebAPI::direct_get($this->api_url . __JIRA_SOFTWARE_CLOUD_REST_API . "board/" . __JIRA_CBTS_BOARD_ID . "/sprint/{$sprint_id}/issue", $parameters, true, true, $headers);
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

                        $issue["parent_key"]  = $parent["key"];
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

        return $task_list;
    }

    public function get_child_issue_list($parent_key) {
        $task_list = array();

        $parameters               = array();
        $parameters["fields"]     = "summary";
        $parameters["maxResults"] = 100;
        $parameters["jql"]        = "parent={$parent_key} and statusCategory in (2, 4) and project=CBTS order by key";//リリース完了以外

        $headers   = array();
        $headers[] = "Authorization: Basic " . base64_encode(__JIRA_LOGIN_ID . ":" . __JIRA_API_TOKEN);
        list($status_code, $response) = WebAPI::direct_get($this->api_url . __JIRA_CLOUD_PLATFORM_REST_API . "search", $parameters, true, true, $headers);
        if ($status_code == "200") {
            $datas = $response["issues"];
            if (count($datas) > 0) {
                foreach ($datas as $data) {
                    $fields = $data["fields"];

                    $issue                   = array();
                    $issue["key"]            = $data["key"];
                    $issue["name"]           = $fields["summary"];
                    $task_list[$data["key"]] = $issue;
                }
            }
        }

        return $task_list;
    }

    public function get_active_issue_list($assignee) {
        $task_list = array();

        //activeなsprint_idの取得
        $sprint_list = $this->get_sprint_list(__JIRA_CBTS_BOARD_ID, "active");
        $sprint_id   = 0;
        if (count($sprint_list)) {
            $sprint_id = array_key_first($sprint_list);
        }

        $parameters               = array();
        $parameters["fields"]     = "summary";
        $parameters["maxResults"] = 100;
        $jql                      = "";
        if ($assignee) {
            $jql = "assignee = '{$assignee}' ";
        }
        $parameters["jql"] = $jql . "order by key";

        $headers   = array();
        $headers[] = "Authorization: Basic " . base64_encode(__JIRA_LOGIN_ID . ":" . __JIRA_API_TOKEN);
        list($status_code, $response) = WebAPI::direct_get($this->api_url . __JIRA_SOFTWARE_CLOUD_REST_API . "board/" . __JIRA_CBTS_BOARD_ID . "/sprint/{$sprint_id}/issue", $parameters, true, true, $headers);
        if ($status_code == "200") {
            $datas = $response["issues"];
            if (count($datas) > 0) {
                foreach ($datas as $data) {
                    $fields = $data["fields"];

                    $issue                   = array();
                    $issue["key"]            = $data["key"];
                    $issue["name"]           = $fields["summary"];
                    $task_list[$data["key"]] = $issue;
                }
            }
        }

        return $task_list;
    }

    public function get_sprint($sprint_id) {
        $headers   = array();
        $headers[] = "Authorization: Basic " . base64_encode(__JIRA_LOGIN_ID . ":" . __JIRA_API_TOKEN);
        list($status_code, $response) = WebAPI::direct_get($this->api_url . __JIRA_SOFTWARE_CLOUD_REST_API . "sprint/{$sprint_id}", array(), true, true, $headers);
        if ($status_code == "200") {
            return $response;
        }
        return false;
    }

    public function get_target_issue_list($board_id, $sprint_ids) {
        $parameters           = array();
        $parameters["fields"] = "summary";
        $jql                  = "sprint in ( " . join(",", $sprint_ids) . " )";
        if ($board_id == __JIRA_CBTS_OLD_BOARD_ID) {
            $jql .= " AND issuetype = Story";
        } else if ($board_id == __JIRA_CBTS_BOARD_ID) {
            $jql .= " AND issuetype = ストーリー";
            $jql .= " AND parent in ('CBTS-168','CBTS-2296','CBTS-279')";
        }
        $parameters["jql"] = $jql . " order by key";

        $headers   = array();
        $headers[] = "Authorization: Basic " . base64_encode(__JIRA_LOGIN_ID . ":" . __JIRA_API_TOKEN);
        list($status_code, $response) = WebAPI::direct_get($this->api_url . __JIRA_CLOUD_PLATFORM_REST_API . "search", $parameters, true, true, $headers);
        $total = 0;
        if ($status_code == "200") {
            $total = $response["total"];
        }

        $task_list = array();
        if ($total) {
            $parameters["maxResults"] = 100;
            $parameters["fields"]     = "summary,customfield_10020";
            $startAt                  = 0;
            while ($total > 0) {
                $parameters["startAt"] = $startAt;

                $headers   = array();
                $headers[] = "Authorization: Basic " . base64_encode(__JIRA_LOGIN_ID . ":" . __JIRA_API_TOKEN);
                list($status_code, $response) = WebAPI::direct_get($this->api_url . __JIRA_CLOUD_PLATFORM_REST_API . "search", $parameters, true, true, $headers);
                if ($status_code == "200") {
                    $datas = $response["issues"];
                    if (count($datas) > 0) {
                        foreach ($datas as $data) {
                            $fields = $data["fields"];

                            if (strpos($fields["summary"], "【棄却】") === FALSE) {
                                $issue                   = array();
                                $issue["key"]            = $data["key"];
                                $issue["name"]           = $fields["summary"];
                                $issue["sprint_id"]      = isset($fields["customfield_10020"][0]["id"]) ? $fields["customfield_10020"][0]["id"] : 0;
                                $task_list[$data["key"]] = $issue;
                            }
                        }
                    }
                }

                $total   -= 100;
                $startAt = 100;
            }
        }

        return $task_list;
    }

}
