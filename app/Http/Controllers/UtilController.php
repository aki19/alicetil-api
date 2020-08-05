<?php

namespace App\Http\Controllers;

use App\Libraries\HashUtil;
use Illuminate\Http\Request;

class UtilController extends Controller {

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        //
    }

    public function encryption(Request $request) {
        $id       = $request->id;
        $password = $request->password;
        if ($id && $password) {
            return response()->json(array("encrypted_password" => HashUtil::stretching($password, $id)), 200);
        } else {
            return response()->json('invalid parameter', 400);
        }
    }

    public function convert_listloader(Request $request) {
        $const_name = $request->const_name;
        $post_input = $request->input;

        $input_list = explode("\n", $post_input);

        $json_list = array();

        foreach ($input_list as $input) {
            preg_match('/\((.*?)\)/i', $input, $m);
            if (isset($m[1]) && $m[1]) {
                $str = explode(",", $m[1]);
                if (count($str) == 2) {
                    $const       = str_replace("\"", "", $str[0]);
                    $json_list[] = "\$list[\"" . $const_name . "\"][" . $const . "] = __LABEL" . substr($const, 1) . ";";
                }
            }
        }

        return response()->json(array(implode("\n", $json_list)), 200);
    }

}
