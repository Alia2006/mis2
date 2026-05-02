<?php

namespace app\admin\controller\mis;

use app\common\controller\Backend;

/**
 * 项目类型管理
 */
class Projecttypetable extends Backend
{
    /**
     * Projecttypetable模型对象
     * @var object
     * @phpstan-var \app\admin\model\mis\Projecttypetable
     */
    protected object $model;

    protected string|array $defaultSortField = 'ID,desc';

    protected string|array $quickSearchField = ['ID'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new \app\admin\model\mis\Projecttypetable();
    }


    /**
     * 若需重写查看、编辑、删除等方法，请复制 @see \app\admin\library\traits\Backend 中对应的方法至此进行重写
     */
}