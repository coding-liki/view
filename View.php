<?php

namespace CodingLiki\View;

use CodingLiki\Configs\Config;

/**
 * Класс для парсинга представлений с возможностью создания слотов
 */

class View{
    public static $all_values = []; // Список всех подключённых переменных за время сессии
    public $values = null; // Список подключённых переменных в текущей сессии
    public $slots = []; // Список созданных слотов и дефолтные значения в них

    public $slots_to_set = []; // Слоты, которые нужно заполнить в наследуемом шаблоне

    public $result_text; // Текст, который в итоге необходимо вывести 
    public $parent_view = null; // Здесь хранится объект наследуемого шаблона

    public $last_created_slot; // Имя последнего созданного слота
    public $last_edited_slot; // Имя последнего использованного слота
    public $creating_slot = false; // Флаг режима создания слота
    public $editing_slot = false; // Флаг режима использования слота
    public $template_path = ""; // Путь до файла с представлением
    public $need_render = true; // Флаг необходимости вывода результата

    /**
     * Функция для наследования другого представления
     *
     * @param [type] $view_name
     * @return void
     */
    public function extends($view_name){
        $this->parent_view = new View();

        $this->result_text = $this->parent_view->parse($view_name, $this->values);
        
        $this->slots_to_set = $this->parent_view->slots;
    }

    /**
     * Функция парсинга представления с учётом подключаемых переменных
     *
     * @param [type] $view_name
     * @param array $values
     * @return void
     */
    public function parse($view_name, $values = []){
        $views_folder = Config::config("main.views_folder") ?? "Views";

        $this->template_path = $views_folder."/$view_name.php";
        $this->values = $values;
        if (!empty($values)) {
            static::$all_values = array_merge(static::$all_values, $values);
        }
        if(file_exists($this->template_path)){
            // echo "Можно подключать";
            foreach(static::$all_values as $key => $value){
                $$key = $value;
            }

            ob_start();
            include $this->template_path;
            $this->result_text = ob_get_contents();
            ob_end_clean();

            return $this->render();
        }
        

    }

    /**
     * Функция для вывода представления с учётом наследованния и слотов
     *
     * @return void
     */
    public function render(){
        $result = $this->result_text;
        foreach($this->slots_to_set as $slot_name => $slot){
            $result = str_replace('{{slot_'.$slot_name.'}}', $slot, $result);
        }

        if ($this->need_render) {
            echo $result;
        }
        return $result;
    }

    /**
     * Задаём новое значение для слота
     *
     * @param [type] $slot_name
     * @return void
     */
    public function slot($slot_name){
        if($this->editing_slot){
            return;
        }
        $this->editing_slot = true;
        ob_start();
        $this->last_edited_slot = $slot_name;
    }

    /**
     * Завершаем задание нового значения для слота
     *
     * @return void
     */
    public function endSlot(){
        if(!$this->editing_slot){
            return;
        }
        $this->editing_slot = false;
        $this->slots_to_set[$this->last_edited_slot] = ob_get_contents();
        ob_end_clean();
    }

    /**
     * Создаём новый слот
     *
     * @param [type] $slot_name
     * @return void
     */
    public function createSlot($slot_name){
        if($this->creating_slot){
            return;
        }

        $this->creating_slot = true;

        echo '{{slot_'.$slot_name.'}}';
        ob_start();
        $this->last_created_slot = $slot_name;
    }

    /**
     * Завершаем создание нового слота и задаём ему дефолтное значение
     *
     * @return void
     */
    public function stopCreateSlot(){
        if(!$this->creating_slot){
            return;
        }

        $this->creating_slot = false;

        $result = ob_get_contents();
        ob_end_clean();

        $this->slots[$this->last_created_slot] = $result;
    }
}