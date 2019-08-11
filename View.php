<?php

namespace CodingLiki\View;

use CodingLiki\Configs\Config;

/**
 * Класс для парсинга представлений с возможностью создания слотов
 */

class View{
    protected static $my_objects_stack = [];
    protected static $my_object_counter = 0;
    const END_PREPEND = 0;
    const END_SUBSTITUTE = 1;
    const END_APPEND = 2; 
    public static $all_values = []; // Список всех подключённых переменных за время сессии
    public $values = null; // Список подключённых переменных в текущей сессии
    public static $slots = []; // Список созданных слотов и дефолтные значения в них
    public $template_vars = []; // Список созданных шаблонных переменных, для хранения кусков текста
    public $slots_to_set = []; // Слоты, которые нужно заполнить в наследуемом шаблоне

    public $result_text; // Текст, который в итоге необходимо вывести 
    public $parent_view = null; // Здесь хранится объект наследуемого шаблона

    public $last_created_slot; // Имя последнего созданного слота
    public $last_edited_slot; // Имя последнего использованного слота
    public $last_created_var; // Имя последней созданной шаблонной переменной

    public $creating_slot = false; // Флаг режима создания слота
    public $editing_slot = false; // Флаг режима использования слота
    public $creating_var = false; //Флаг режима создания шаблонной переменной

    public $template_path = ""; // Путь до файла с представлением
    public $need_render = true; // Флаг необходимости вывода результата
    public $extension  = ".php";
    public function __construct()
    {
        static::$my_objects_stack[static::$my_object_counter] = $this;
        static::$my_object_counter++;
    }

    public static function getLastObject(){
        return static::$my_objects_stack[static::$my_object_counter-1];
    }
    /**
     * Функция для наследования другого представления
     *
     * @param [type] $view_name
     * @return void
     */
    public function extends($view_name){
        $this->parent_view = new View();

        $this->result_text = $this->parent_view->parse($view_name, $this->values);
        
        $this->slots_to_set = static::$slots+$this->parent_view->slots_to_set;
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

        $this->template_path = $views_folder."/$view_name".$this->extension;
        $this->values = $values;
        if (!empty($values)) {
            static::$all_values = array_merge(static::$all_values, $values);
        }
        if($view_name == ""){
            return true;
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
            
            static::$my_object_counter--;
            unset(static::$my_objects_stack[static::$my_object_counter]);

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
    public function endSlot($end_type = self::END_SUBSTITUTE){
        if(!$this->editing_slot){
            return;
        }
        $this->editing_slot = false;
        switch($end_type){
            case self::END_APPEND:
                if(!isset($this->slots_to_set[$this->last_edited_slot])){
                    $this->slots_to_set[$this->last_edited_slot] = ob_get_contents();
                } else {
                    $this->slots_to_set[$this->last_edited_slot] .= ob_get_contents();
                }
                break;
            case self::END_PREPEND:
                $this->slots_to_set[$this->last_edited_slot] = ob_get_contents().$this->slots_to_set[$this->last_edited_slot];
                break;
            
            case self::END_SUBSTITUTE:
            default:
                $this->slots_to_set[$this->last_edited_slot] = ob_get_contents();
                break;
        }
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

        static::$slots[$this->last_created_slot] = $result;
    }

    /****************************************** */
    /**
     * Создаём новую шаблонную переменную
     *
     * @param [type] $slot_name
     * @return void
     */
    public function createVar($var_name){
        if($this->creating_var){
            return;
        }

        $this->creating_var = true;

        ob_start();
        $this->last_created_var = $var_name;
    }

    /**
     * Завершаем создание новой шаблонной переменной и сохраняем её значение
     *
     * @return void
     */
    public function stopCreateVar(){
        if(!$this->creating_var){
            return;
        }

        $this->creating_var = false;

        $result = ob_get_contents();
        ob_end_clean();

        $this->template_vars[$this->last_created_var] = $result;
    }

    public function getVar($var_name, $default_value = ""){
        if(isset($this->template_vars[$var_name])){
            return $this->template_vars[$var_name];
        } else {
            return $default_value;
        }
    }

}