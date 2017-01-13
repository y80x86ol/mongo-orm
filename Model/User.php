<?php

/**
 * 用户表
 */

namespace Model;

use mongoOrm\Model;


class User extends Model {
    public $dbName = 'mongo_orm';
    public $collName = 'users';

    public function fields() {
        return [
            'username' => [
                'type' => 'string',
                'default' => '1111'
            ],
            'age' => [
                'type' => 'int',
                'default' => 0
            ],
            'address' => [
                'type' => 'array',
                'default' => [
                    'zipCode' => 10000,
                    'area' => 'zh-cn'
                ]
            ]
        ];
    }

    public function article() {
        $this->hasMore("\article", '', '');
    }
}