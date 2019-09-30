<?php
use CodingLiki\View\View;

function view($view_name, $values = [], $need_render = true){
    
    /** Обёртки для внутреннего использования в представлениях */
    if (!function_exists("extend")) {
        function extend($view_name)
        {
            $last = View::getLastObject();
            $last->extends($view_name);
        }
    }

    if (!function_exists("slot")) {
        function slot($slot_name)
        {
            $last = View::getLastObject();
            $last->slot($slot_name);
        }
    }

    if (!function_exists("createSlot")) {
        function createSlot($slot_name)
        {
            $last = View::getLastObject();
            $last->createSlot($slot_name);
        }
    }

    if (!function_exists("endSlot")) {
        function endSlot()
        {
            $last = View::getLastObject();
            $last->endSlot();
        }
    }

    if (!function_exists("stopCreateSlot")) {
        function stopCreateSlot()
        {
            $last = View::getLastObject();
            $last->stopCreateSlot();
        }
    }

    /***************************************/
    $v = new View();
    $v->need_render = $need_render;
    $result = $v->parse($view_name, $values);

    if($need_render){
        return $result;
    }
}

function print_g($var){
    echo "<pre>";
    print_r($var);
    echo "</pre>";
}

