<?php

namespace app\admin\validate\workflow;

use think\Validate;

class Definition extends Validate
{
    protected $failException = true;

    protected $rule = [
        'name' => 'require|length:1,100',
        'code' => 'require|alphaDash|length:1,50',
    ];

    protected $message = [
        'name.require'    => '流程名称不能为空',
        'name.length'     => '流程名称长度需在1-100个字符',
        'code.require'    => '流程编码不能为空',
        'code.alphaDash'  => '流程编码只能包含字母、数字、下划线和破折号',
        'code.length'     => '流程编码长度需在1-50个字符',
    ];

    protected $scene = [
        'add'  => ['name', 'code'],
        'edit' => ['name', 'code'],
    ];
}
