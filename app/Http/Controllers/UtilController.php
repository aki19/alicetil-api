<?php

namespace App\Http\Controllers;

class UtilController extends Controller {

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        //
    }

    public function encrypted_password($id) {
        //TODO
        return $id . ":hello";
    }

}
