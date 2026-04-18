<?php


namespace app\Interfaces;


interface Payments {
    public function generatetoken($request);
    public function readkey($key);
}
