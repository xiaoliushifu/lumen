<?php
/**
 * User: masterliu
 * Date: 2018/10/17
 * Time: 上午0:23
 */
namespace App\Validates;

use Illuminate\Support\Facades\Validator;

/**
 * 只针对User控制器的验证类
 * Class DemandValidate
 * @package App\Validates
 */
class UserValidate extends Validator
{

    /**
     * 定义场景常量
     */
    const SCENARIO_LIST = 'list';

    /**
     * 初始化待验证的字段
     */
    public $input;

    /**
     * 字段和场景的对应，
     * 控制器的操作一般对应一种场景（创建，查看，编辑，更新等）
     * 而每个场景下可以有多个字段，每个字段可以有多个规则
     * 不在对应场景下的字段不会被验证
     * @var array
     */
    public $rule = [
        self::SCENARIO_LIST => [
            'phone' => ['required', 'regex:/^(13[0-9]|14[5-9]|15[012356789]|166|17[0-8]|18[0-9]|19[8-9])[0-9]{8}$/'],
            'name' => ['required','in:1,2,3,4'],
        ],
    ];

    /**
     * 已经支持中文语言库
     */
    protected $message = [
    ];

    /**
     * 定义场景和场景下的字段，一个场景下可以有多个字段
     * 字段顺序决定了验证顺序
     * @var array
     */
    protected $scene = [
        self::SCENARIO_LIST => ['phone', 'name'],
    ];

    /**
     * check是验证的入口
     * @param $inputs
     * @param $scene 场景 必填
     * @return bool|string
     */
    public function check($inputs, $scene)
    {

        //根据场景获得对应的字段
        $input = $this->getInput($inputs, $scene);
        //根据场景获得该场景下的规则
        $rules = $this->getRules($scene);
        //根据规则获得对应的错误信息
        $messages = $this->getMessage($rules);
        //使用laravel提供的原生验证
        $validator = Validator::make($input, $rules, $messages);

        //返回错误信息
        if ($validator->fails()) {
            return $validator->errors()->first(); //返回错误信息
        }
        //false表示验证通过，没有错误
        return false;
    }

    /**
     * 根据场景获得对应的字段，不在当前场景的不会获取，更不会进行后续的验证
     * @param $inputs
     * @param $scene
     * @return mixed
     */
    public function getInput($inputs, $scene)
    {
        $input = [];
        foreach ($this->scene[$scene] as $field) {
            if (array_key_exists($field, $inputs)) {
                $input[$field] = $inputs[$field];
            }
        }
        return $input;
    }

    /**
     *
     * 根据场景获取验证规则
     * @param $scene
     * @return mixed
     */
    public function getRules($scene)
    {
        $rules = [];
        if ($this->scene[$scene]) {
            foreach ($this->scene[$scene] as $field) {
                if (array_key_exists($field, $this->rule[$scene])) {
                    $rules[$field] = $this->rule[$scene][$field];
                }
            }
        }
        return $rules;
    }


    /**
     *
     * 根据$rules里指定的验证规则返回对应的message
     * @param $rules
     * $rules的数据结构就是
     *  ```
     *      'phone' => ['required', 'regex:/^(13[0-9]|14[5-9]|15[012356789]|166|17[0-8]|18[0-9]|19[8-9])[0-9]{8}$/'],
            'name' => ['required','in:1,2,3,4'],
     *  ```
     * @return mixed
     */
    public function getMessage($rules)
    {
        $message = [];
        foreach ($rules as $key => $v) {
            $arr = is_array($v) ? $v : explode('|', $v);
            foreach ($arr as $k => $val) {
                if (strpos($val, ':')) {
                    unset($arr[$k]);
                    $arr[] = substr($val, 0, strpos($val, ':'));
                }

            }
            foreach ($arr as $value) {
                if (array_key_exists($key . '.' . $value, $this->message)) {
                    $message[$key . '.' . $value] = $this->message[$key . '.' . $value];
                }
            }
        }
        return $message;
    }

}