<?php namespace Gdoo\Customer\Controllers;

use DB;
use Request;
use Validator;

use Gdoo\User\Models\User;
use Gdoo\Customer\Models\CustomerPrice;

use Gdoo\Model\Grid;
use Gdoo\Model\Form;

use Gdoo\Product\Models\Product;

use Gdoo\Index\Controllers\DefaultController;

class PriceController extends DefaultController
{
    public $permission = ['dialog', 'list', 'referCustomer'];

    public function indexAction()
    {
        $header = Grid::header([
            'code' => 'customer_price',
            'referer' => 1,
            'search' => ['by' => ''],
        ]);

        $cols = $header['cols'];

        $cols['actions']['options'] = [[
            'name' => '编辑',
            'action' => 'edit',
            'display' => $this->access['edit'],
        ]];

        $search = $header['search_form'];
        $query = $search['query'];

        if (Request::method() == 'POST') {
            $model = DB::table($header['table'])->setBy($header);
            foreach ($header['join'] as $join) {
                $model->leftJoin($join[0], $join[1], $join[2], $join[3]);
            }
            $model->orderBy($header['sort'], $header['order']);
            $model->where('customer_id_customer.id', '>', 0);
            $model->where('product_id_product.id', '>', 0);
            $model->where('product_id_product.status', 1);

            // 客户权限
            $region = regionCustomer('customer_id_customer');
            if ($region['authorise']) {
                foreach ($region['whereIn'] as $key => $where) {
                    $model->whereIn($key, $where);
                }
            }
            
            foreach ($search['where'] as $where) {
                if ($where['active']) {
                    $model->search($where);
                }
            }

            $model->select($header['select']);
            $rows = $model->paginate($query['limit'])->appends($query);
            $items = Grid::dataFilters($rows, $header);
            return $items->toJson();
        }

        $header['buttons'] = [
            ['name' => '删除', 'icon' => 'fa-remove', 'action' => 'delete', 'display' => $this->access['delete']],
            ['name' => '导出', 'icon' => 'fa-share', 'action' => 'export', 'display' => 1],
        ];

        $header['right_buttons'] = [
            ['name' => '导入', 'icon' => 'fa-mail-reply', 'color' => 'default', 'action' => 'import', 'display' => $this->access['import']],
        ];

        $header['cols'] = $cols;
        $header['tabs'] = CustomerPrice::$tabs;
        $header['bys'] = CustomerPrice::$bys;
        $header['js'] = Grid::js($header);

        return $this->display([
            'header' => $header,
        ]);
    }

    // 新建促销
    public function createAction($action = 'edit')
    {
        $id = (int)Request::get('id');
        $form = Form::make(['code' => 'customer_price', 'id' => $id, 'action' => $action]);
        return $this->display([
            'form' => $form,
        ], 'create');
    }

    // 编辑促销
    public function editAction()
    {
        return $this->createAction();
    }

    // 显示促销
    public function showAction()
    {
        return $this->createAction('edit');
    }

    // 客户价格列表
    public function listAction()
    {
        $gets = Request::all();
        if ($gets['customer_id']) {
            $header = Grid::header([
                'code' => 'customer_price',
            ]);
            $model = CustomerPrice::where('customer_id', $gets['customer_id']);
            foreach ($header['join'] as $join) {
                $model->leftJoin($join[0], $join[1], $join[2], $join[3]);
            }

            $model->where('product_id_product.status', 1);
            $model->orderBy('customer_price.id', 'asc');

            $rows = $model->get($header['select']);
            $rows = Grid::dataFilters($rows, $header, function($row) {
                $row['product_name'] = $row['product_id_name'];
                return $row;
            });
        } else {
            $rows = [];
        }
        return $this->json($rows, true);
    }

    // 参考客户价格
    public function referCustomerAction()
    {
        $search = search_form(
            ['advanced' => ''], [
                ['form_type' => 'dialog', 'name' => '客户', 'field' => 'customer_price.customer_id', 'options' => [
                    'url' => 'customer/customer/dialog', 'query' => ['multi'=>0]
                ]],
        ], 'model');
        
        if (Request::method() == 'POST') {

            $active = false;
            foreach ($search['where'] as $where) {
                if ($where['active']) {
                    $active = true;
                }
            }
            
            if ($active) {
                $model = CustomerPrice::query();
                $header = Grid::header([
                    'code' => 'customer_price',
                ]);
                foreach ($header['join'] as $join) {
                    $model->leftJoin($join[0], $join[1], $join[2], $join[3]);
                }
                $model->where('product_id_product.status', 1);
                $model->orderBy('customer_price.id', 'asc');

                foreach ($search['where'] as $where) {
                    if ($where['active']) {
                        $model->search($where);
                    }
                }
                $rows = $model->get($header['select']);
                $rows = Grid::dataFilters($rows, $header, function($row) {
                    $row['product_name'] = $row['product_id_name'];
                    return $row;
                });
            } else {
                $rows = [];
            }
            return $this->json($rows, true);
        }
        $search['query']['id'] = 'refer_customer';
        return $this->render([
            'search' => $search,
        ]);
    }

    // 数据导入
    public function importAction()
    {
        if (Request::method() == 'POST') {
            return Form::import(['table' => 'customer_price', 'keys' => ['customer_id', 'product_id']]);
        }
        $tips = '注意：表格里必须包含[存货编码,客户代码]列。';
        return $this->render(['tips' => $tips], 'layouts.import');
    }

    // 删除促销
    public function deleteAction()
    {
        if (Request::method() == 'POST') {
            $ids = Request::get('id');
            return Form::remove(['code' => 'customer_price', 'ids' => $ids]);
        }
    }
}
