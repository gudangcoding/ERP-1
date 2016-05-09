<?php

namespace Torg\Erp\Sales;

use Torg\Erp\Contracts\DocumentInterface;
use Torg\Base\Company;
use Torg\Base\Warehouse;
use Torg\Erp\Stocks\Exceptions\StockException;
use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Torg\Erp\Sales\Order
 *
 * @SWG\Definition (
 *      definition="Order",
 *      required={},
 *      @SWG\Property(
 *          property="id",
 *          description="id",
 *          type="integer",
 *          format="int32"
 *      ),
 *      @SWG\Property(
 *          property="code",
 *          description="code",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="company_id",
 *          description="company_id",
 *          type="integer",
 *          format="int32"
 *      ),
 *      @SWG\Property(
 *          property="created_at",
 *          description="created_at",
 *          type="string",
 *          format="date-time"
 *      ),
 *      @SWG\Property(
 *          property="updated_at",
 *          description="updated_at",
 *          type="string",
 *          format="date-time"
 *      )
 * )
 * @property-read \Torg\Base\Warehouse $warehouse
 * @property-read \Torg\Base\Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection|\Torg\Erp\Sales\OrderItem[] $items
 * @mixin \Eloquent
 * @property integer $id
 * @property string $code
 * @property integer $status_id
 * @property integer $warehouse_id
 * @property integer $company_id
 * @property integer $customer_id
 * @property string $customer_name
 * @property string $customer_email
 * @property float $weight
 * @property float $volume
 * @property float $total_qty
 * @property float $total
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * @method static \Illuminate\Database\Query\Builder|\Torg\Erp\Sales\Order whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Torg\Erp\Sales\Order whereCode($value)
 * @method static \Illuminate\Database\Query\Builder|\Torg\Erp\Sales\Order whereStatusId($value)
 * @method static \Illuminate\Database\Query\Builder|\Torg\Erp\Sales\Order whereWarehouseId($value)
 * @method static \Illuminate\Database\Query\Builder|\Torg\Erp\Sales\Order whereCompanyId($value)
 * @method static \Illuminate\Database\Query\Builder|\Torg\Erp\Sales\Order whereCustomerId($value)
 * @method static \Illuminate\Database\Query\Builder|\Torg\Erp\Sales\Order whereCustomerName($value)
 * @method static \Illuminate\Database\Query\Builder|\Torg\Erp\Sales\Order whereCustomerEmail($value)
 * @method static \Illuminate\Database\Query\Builder|\Torg\Erp\Sales\Order whereWeight($value)
 * @method static \Illuminate\Database\Query\Builder|\Torg\Erp\Sales\Order whereVolume($value)
 * @method static \Illuminate\Database\Query\Builder|\Torg\Erp\Sales\Order whereTotalQty($value)
 * @method static \Illuminate\Database\Query\Builder|\Torg\Erp\Sales\Order whereTotal($value)
 * @method static \Illuminate\Database\Query\Builder|\Torg\Erp\Sales\Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Torg\Erp\Sales\Order whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Torg\Erp\Sales\Order whereDeletedAt($value)
 */
class Order extends Model implements DocumentInterface
{
    use SoftDeletes;

    /**
     * @var string
     */
    public $table = 'orders';

    /**
     * @var array
     */
    public $with = ['company', 'warehouse'];


    /**
     * @var array
     */
    protected $dates = ['deleted_at', 'created_at', 'updated_at'];

    /**
     * @var
     */
    public static $itemInstance = OrderItem::class;


    /**
     * @var array
     */
    protected $attributes = [
        'weight' => 0,
        'volume' => 0,
        'items_count' => 0,
        'products_qty' => 0,
        'grand_total' => 0,
        'subtotal'=>0,
        'shipping_amount'=>0,
        'order_discount'=>0,
        'payment_total'=>0,
        'due_total'=>0,

    ];

    /**
     * @var array
     */
    protected $fillable = [
        'code',
        'warehouse_id',
        'customer_id',
        'customer_name',
        'customer_email',
        'weight',
        'volume',
        'items_count',
        'products_qty',
        'grand_total',
        'subtotal',
        'shipping_amount',
        'order_discount',
        'payment_total',
    ];

    protected $guarded = ['due_total'];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'code' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [

    ];


    /**
     *
     */
    public static function boot()
    {

        parent::boot();

        static::saving(function (Order $order) {
           // $order->checkCompany();
        });
    }


    /**
     *
     */
    public function checkCompany()
    {


        $orgFromWarehouse = $this->getWarehouse()->company;

        $newWarehouseId = $this->attributes['warehouse_id'];


        if($newWarehouseId != $this->getOriginal('warehouse_id')) {


            $newWarehouse = Warehouse::find($newWarehouseId)->first();

            if($newWarehouse->company->id != $orgFromWarehouse->id)
                throw new StockException('для заказ нельзя установить склад из другой организации');
        }

        $this->company()->associate($orgFromWarehouse);
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(static::$itemInstance, 'order_id');
    }




    /**
     * @param OrderItem $item
     */
    public function add(OrderItem $item)
    {

        $this->items()->save($item);
        $this->load('items'); //баг в Laravel. При добавление связанного объекта, коллекция не обновляется и надо ее загрузить заново
    }


    /**
     * @return Company
     */
    public function getCompany()
    {
        return $this->getWarehouse()->company;
    }

    /**
     * @return Warehouse
     */
    public function getWarehouse()
    {
        return $this->warehouse;
    }

    /**
     * @return mixed
     */
    public function getItems()
    {
        return $this->items;
    }
    
    public function getId()
    {
        return $this->id;
    }
}
