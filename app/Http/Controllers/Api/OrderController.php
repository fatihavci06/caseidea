<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderProduct;
use DB;
class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //

       $validator = \Validator::make($request->all(), [
             'customerId' => 'required|integer|min:1|max:99999',
             'id' => 'required|integer|min:1|max:99999',
             'items.*.productId' => 'required|numeric|between:0,99999.99',
              'items.*.quantity' => 'required|numeric|between:0,99999.99',
              'items.*.unitPrice' => 'required|numeric|between:0,9999.99',
              'items.*.total' => 'required|numeric|between:0,99999.99',
              'total' =>'required|numeric|between:0,99999.99',
                
            ]);
        if ($validator->fails())
         {
        return response()->json(['errors'=>$validator->errors()->all()]);
        }
       

        $data= $request->all();

       $donen="";
       
          foreach($data['items'] as $s){
          $control=Product::where('id',$s['productId'])->count();

              if($control!=1){
                return response()->json(['err'=>$s["productId"].' idli ürün veritabanımızda kayıtlı değildir.']);

              }
              else{

                $stock=Product::select('stock')->where('id',$s['productId'])->first();

                $stok=$stock->stock;
                if(  $stok < $s['quantity'] ){
                    $donen='lütfen '.$s['productId'].' idli ürün için uygun stok giriniz.';
                    return response()->json($donen);
                }


             
            
             }
         }

          $i=0;
           $response['order_id']=0;
          $response['totalDiscount']=0;
          $response['discountedTotal']=0;
          $response['discounts']=[];
         if($data['total']>1000){
            $data['total']=$data['total']*90/100;
             $response['discounts'][$i]['discountReason']="10_PERCENT_OVER_1000";

                   $response['discounts'][$i]['discountAmount']=$data['total']/10;
                   $response['totalDiscount']+=round(($response['discounts'][$i]['discountAmount']),2);
                   $response['discountedTotal']+=$response['discounts'][$i]['subtotal']=round(($data['total']*90/100),2);
            $i++;
         }



          $order=Order::insertGetId([
                    'customer_id'=>$data['customerId'],
                    'total'=>$data['total'],
                ]);
           $response['order_id']=$order;
          
          
         
          DB::statement('SET FOREIGN_KEY_CHECKS=0;');
         
          foreach($data['items'] as $s){


                 $category=Product::select('category')->where('id',$s['productId'])->first();
                 $category_id=$category->category;

                 if($category_id==2 && $s['quantity']>=6) {
                   
                
                 $quantity=floor($s['quantity']/6);
                   $s['total']=($s['quantity']-$quantity)*$s['unitPrice'];
                    $response['discounts'][$i]['discountReason']="BUY_5_GET_1";
                   $response['totalDiscount']+=$response['discounts'][$i]['discountAmount']=
                   round(($s['quantity']*$s['unitPrice'])-$s['total'],2

               );
                    $response['discountedTotal']+=$response['discounts'][$i]['subtotal']=round($s['total'],2);
                 }

                OrderProduct::create([
                    'order_id'=>$order,
                    'product_id'=>$s['productId'],
                    'quantity'=>$s['quantity'],
                    'unitPrice'=>$s['unitPrice'],
                    'total'=>$s['total']
                ]);
                $product=Product::where('id',$s['productId'])->first();
                $product->stock=$product->stock-$s['quantity'];
                $product->save();

          }
          DB::statement('SET FOREIGN_KEY_CHECKS=1;');

          return response()->json($response);

       
       
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($customer_id)
    {
        //
        $orders=Order::with('orderProducts.product_name')->where('customer_id',$customer_id)->get();

        return response()->json(['orders'=>$orders]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        $validator = \Validator::make($request->all(), [
             'customerId' => 'required|integer|min:1|max:99999',
             'id' => 'required|integer|min:1|max:99999',
             'items.*.productId' => 'required|numeric|between:0,99999.99',
              'items.*.quantity' => 'required|numeric|between:0,99999.99',
              'items.*.unitPrice' => 'required|numeric|between:0,9999.99',
              'items.*.total' => 'required|numeric|between:0,99999.99',
              'total' =>'required|numeric|between:0,99999.99',
                
            ]);
        if ($validator->fails())
         {
        return response()->json(['errors'=>$validator->errors()->all()]);
        }
       

        $data= $request->all();

       $donen="";
       
          foreach($data['items'] as $s){
          $control=Product::where('id',$s['productId'])->count();

              if($control!=1){
                return response()->json(['err'=>$s["productId"].' idli ürün veritabanımızda kayıtlı değildir.']);

              }
              else{

                $stock=Product::select('stock')->where('id',$s['productId'])->first();

                $stok=$stock->stock;
                if(  $stok < $s['quantity'] ){
                    $donen='lütfen '.$s['productId'].' idli ürün için uygun stok giriniz.';
                    return response()->json($donen);
                }


             
            
             }
         }

          $order=Order::where('id',$id)->update([
                    'customer_id'=>$data['customerId'],
                    'total'=>$data['total'],
                ]);
          DB::statement('SET FOREIGN_KEY_CHECKS=0;');
          foreach($data['items'] as $s){
                 $oldQuantity=OrderProduct::where('product_id',$s['productId'])->first();
                  $oldQuantity= $oldQuantity->quantity;
                OrderProduct::where('order_id',$id)->update([
                    'order_id'=>$order,
                    'product_id'=>$s['productId'],
                    'quantity'=>$s['quantity'],
                    'unitPrice'=>$s['unitPrice'],
                    'total'=>$s['total']
                ]);

                $product=Product::where('id',$s['productId'])->first();
                $product->stock=$product->stock-$s['quantity']+$oldQuantity;
                $product->save();

          }
          DB::statement('SET FOREIGN_KEY_CHECKS=1;');
          return response()->json(['success'=>'Sipariş güncellendi']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        $order=Order::find($id);

        if(isset($order)){
            $order->delete();
            return response()->json(['message'=>'Başarıyla silindi']);
        }
        else{
            return response()->json(['message'=>'Silinemedi'],404);
        }

      
    }
}

