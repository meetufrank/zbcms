<?php
namespace app\common\validate;
use think\Validate;    
class BsbmValidate extends Validate{
    
    
     /**
     * 规则
     *
     * @var unknown
     */
    protected $rule = [
        'name' => ['require','max'=>30],
        'sex'=>'require',
        'birthday' => 'require',
        'country'=>'require',
        'nation'=>'requireIf:country,1',
        'tel'=>'require',
        'email'=>'require',
        'province'=>'requireIf:country,1',
        'city'=>'requireIf:country,1',
        'district'=>'requireIf:country,1',
        'address'=>['require','max'=>100],
        'cardid'=>'require',
        'idtpl'=>'require',
        'school'=>'require',
        'costid'=>'require',
        'thumb'=>'require',
        'videourl'=>'require',
        'sings'=>'require'
    ];

    /**
     * 提示
     *
     * @var unknown
     */
    protected $message = [
        'name.require' => '姓名必填',
        'name.max' => '姓名最大长度为30',
        'sex.require' => '性别必须选择',
        'birthday.require'=>'出生日期必填',
        'country.require'=>'国家必须选择',
        'nation.requireIf'=>'民族必须选择',
        'tel.require'=>'手机号必须填写',
        'email.require'=>'邮箱必须填写',
        'province.requireIf' => '省必须选择',
        'city.requireIf' => '市必须选择',
        'district.requireIf' => '区必须选择',
        'address.require'=>'详细地址必须填写',
        'address.max' => '详细地址最大长度为100',
        'cardid.require'=>'证件类型必须选择',
        'idtpl.require'=>'证件号必须填写',
        'school.require'=>'学校必须填写',
        'thumb.require'=>'照片必须上传',
        'costid.require'=>'您选择的科目、级别和组别三项费用组合失效,请重新选择',
        'videourl.require'=>'视频链接未填写',
        'sings.require'=>'曲目必须填写'
    ];

    /**
     * 场景
     *
     * @var unknown
     */
    protected $scene = [
        
        'add' => [
            'name',
        'sex',
        'birthday',
        'country',
        'nation',
        'tel',
        'email',
        'province',
        'city',
        'district',
        'address',
        'cardid',
        'idtpl',
        'school',
        'costid',
        'thumb',
        'videourl',
        'sings'
        ]


    ];
}
