<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public $timestamps = false;
    protected $table = "orders";
    protected $fillable = [
      'user_id', 'status_order_id','payment_method', 'quantity', 'promotion', 'total', 'payment_total', 'date'
    ];

    public function promotions()
    {
        return $this->belongsToMany('App\Models\Promotion');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function payment()
    {
        return $this->belongsTo('App\Models\Payment');
    }

    public function typeRooms()
    {
        return $this->belongsToMany('App\Models\TypeRoom');
    }

    public function statusOrder()
    {
        return $this->belongsTo('App\Models\StatusOrder');
    }

    public function orderTypeRooms()
    {
        return $this->hasMany('App\Models\OrderTypeRoom');
    }

    public static function boot()
    {
        parent::boot();

        self::deleting(function ($model) {
            OrderPromotion::whereOrderId($model->id)->delete();
            $typeRooms = OrderTypeRoom::whereOrderId($model->id)->get();
            if (!$typeRooms->isEmpty()) {
                foreach ($typeRooms as $typeRoom) {
                    $orderDetails = OrderDetail::whereOrderTypeRoomId($typeRoom->id)->get();
                    if (!$orderDetails->isEmpty()) {
                        OrderDetail::whereOrderTypeRoomId($typeRoom->id)->delete();
                    }
                }
            }

            OrderTypeRoom::whereOrderId($model->id)->delete();
        });
    }
}
