<?php
/**
 * User: masterliu
 * Date: 2018/10/13
 * Time: 下午5:23
 */
namespace App\Validates;

use Illuminate\Support\Facades\Validator;

/**
 * 只针对Demand这个定义的验证类
 * 确切地说是针对Demand这个模型定义的验证器类，
 * 所有操作Demand模型的地方，都可以引入该类进行验证
 * 只需增加各自的场景即可
 * Class DemandValidate
 * @package App\Validates
 */
class DemandValidate extends Validator
{

    /**
     * 定义场景常量
     */
    const SCENARIO_CREATE = 'create';
    const SCENARIO_RESPONSE = 'response';

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
     * bail只从第一个被验证失败的规则里逃离，但是会继续执行后续其它字段的验证，并不会整体退出
     */
    public $rule = [
        self::SCENARIO_CREATE => [
            'title' => 'bail|required|max:128',
            //regex必须使用数组语法而非"|"
            'phone' => ['required', 'regex:/^1(3|4|5|7|8)\d{9}$/'],
            'area' => 'required|max:35',
            'contact' => 'required|max:128',
            'categories' => 'required|array',
            'budget' => 'required|numeric',
            'response_time' => 'required|date_format:Y-m-d H:i',
            'end_time' => 'required|date_format:Y-m-d H:i',
        ],
        self::SCENARIO_RESPONSE => [
            'user_id' => 'required|size:32',
            'demand_id' => 'required|size:32',
            'content' => 'required|max:128',
        ]
    ];

    /**
     * 填写自定义的错误消息，由于laravel默认的错误消息只有en，
     * 所以不写的话默认都是英文的，后续可以增加中文语言库即可
     */
    protected $message = [
        'budget.required' => '预算不能为空',
    ];

    /**
     * 定义场景和场景下的字段，一个场景下可以有多个字段
     * 字段顺序决定了验证顺序
     * @var array
     */
    protected $scene = [
        // 场景及场景下该验证哪些字段
        self::SCENARIO_CREATE => ['title', 'phone', 'contact', 'categories', 'budget', 'response_time', 'end_time'],
        self::SCENARIO_RESPONSE => ['user_id', 'demand_id', 'content'],
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
        foreach ($this->scene[$scene] as $key => $v) {
            if (array_key_exists($v, $inputs)) {
                $input[$v] = $inputs[$v];
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