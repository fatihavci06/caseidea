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
    public function kural1($toplamtutar,$sinir,$indirimorani,$i,$response){
        if($toplamtutar>=$sinir){
         


         $total=round($total=$toplamtutar*(100-$indirimorani)/100,2);
             $response['discounts'][$i]['discountReason']="10_PERCENT_OVER_1000";

                   $response['discounts'][$i]['discountAmount']=$total*$indirimorani/100;
                   $response['totalDiscount']=round(($response['discounts'][$i]['discountAmount']),2);
                   $response['discountedTotal']=$response['discounts'][$i]['subtotal']=round(($total*(100-$indirimorani)/100),2);
            $i++;
            $response['i']=$i;
          
        }
        else{
          $response['i']=$i;
         
        }
         return $response;
    }

   /* public function kural2($category_id,$hangikategori,$kacadet,$sinir,$ucretsiz_adet,$birimFiyat,$response){
       
       //category_id=2 , hangikategori=2 
      $i=$response['i'];
          if($category_id==$hangikategori && $kacadet>=$sinir) {
                   
                
                 $quantity=floor($kacadet/$sinir);
                   $s['total']=($kacadet-$quantity)*$birimFiyat;
                    $response['discounts'][$i]['discountReason']="BUY_5_GET_1";
                   $response['totalDiscount']+=$response['discounts'][$i]['discountAmount']=
                   round(($kacadet*$birimFiyat)-$s['total'],2);
                    $response['discountedTotal']+=$response['discounts'][$i]['subtotal']=round($s['total'],2);
                 }
            else{
              $response['i']=$i;
            }
            return $response;
    }*/
    public function kural2($products,$hangikategori,$adet,$response){
      
       foreach($products as $p){
                $category=Product::select('category')->where('id',$p['productId'])->first();
                  $category_id=$category->category;
                   $i=$response['i'];
                  $kacadet=$p['quantity'];
                  $birimFiyat=$p['unitPrice'];

          if($category_id==$hangikategori && $kacadet>=$adet) {
                   
                
                 $quantity=floor($kacadet/$adet);
                   $total=($kacadet-$quantity)*$birimFiyat;
                    $response['discounts'][$i]['discountReason']="BUY_5_GET_1";
                   $response['totalDiscount']+=$response['discounts'][$i]['discountAmount']=
                   round(($kacadet*$birimFiyat)-$total,2);
                    $response['discountedTotal']+=$response['discounts'][$i]['subtotal']=round($total,2);
                 }
           
           
       }
       $response['i']=++$response['i'];
        return $response;
    }
    public function kural3($products,$indirimlikategori,$indirimorani,$response){

       $response;
       $adet=0;
        $ucuzolan=9999999999999;
        $i=$response['i'];
        $subtotal=0;
       
      foreach($products as $p){
                $category=Product::select('category')->where('id',$p['productId'])->first();
                $category_id=$category->category;
               
               
                if($category_id==$indirimlikategori){
                  $adet++;
                  if($p['unitPrice']<$ucuzolan){
                    $ucuzolan=$p['unitPrice'];
                  }
                }

                $subtotal+=$p['unitPrice'];

      }

      if($adet>=2){
        $response['discounts'][$i]['discountReason']="20_PERCENT_SAMECATEGORY";
                   $ucuzolan*(100-$indirimorani)/100;
                   $response['discounts'][$i]['discountAmount']=round($ucuzolan*$indirimorani/100,2);
                    $response['discounts'][$i]['subtotal']=round($subtotal-$response['discounts'][$i]['discountAmount'],2);
                     $response['totalDiscount']+=$response['discounts'][$i]['discountAmount'];
                      $response['discountedTotal']+=$response['discounts'][$i]['subtotal'];
                      $response['i']=++$i;

                }
        else{
          $response['i']=$i;
        }

        return $response;
    }
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
                return response()->json(['err'=>$s["productId"].' idli ??r??n veritaban??m??zda kay??tl?? de??ildir.']);

              }
              else{

                $stock=Product::select('stock')->where('id',$s['productId'])->first();

                $stok=$stock->stock;
                if(  $stok < $s['quantity'] ){
                    $donen='l??tfen '.$s['productId'].' idli ??r??n i??in uygun stok giriniz.';
                    return response()->json($donen);
                }


             
            
             }
         }
         $i=0;
          $response['discounts'][$i]['discountReason']=0;
          $response['discounts'][$i]['discountAmount']=0;
          $response['discounts'][$i]['subtotal']=0;
          $response['totalDiscount']=0;
           $response['discountedTotal']=0;
           $response['i']=0;

       $response=$this->kural1($request->total,1000,10,0,$response);
      $response=$this->kural2($data['items'],3,6,$response);   
       $response=$this->kural3($data['items'],1,20,$response);
        
        
         
         $order=Order::insertGetId([
                    'customer_id'=>$data['customerId'],
                    'total'=>$response['discountedTotal'],
                ]);
           $response['order_id']=$order;
          
          
        
          DB::statement('SET FOREIGN_KEY_CHECKS=0;');
         
         foreach($data['items'] as $s){


                
                 

                

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
          unset($response['i']);
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
                return response()->json(['err'=>$s["productId"].' idli ??r??n veritaban??m??zda kay??tl?? de??ildir.']);

              }
              else{

                $stock=Product::select('stock')->where('id',$s['productId'])->first();

                $stok=$stock->stock;
                if(  $stok < $s['quantity'] ){
                    $donen='l??tfen '.$s['productId'].' idli ??r??n i??in uygun stok giriniz.';
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
          return response()->json(['success'=>'Sipari?? g??ncellendi']);
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
            return response()->json(['message'=>'Ba??ar??yla silindi']);
        }
        else{
            return response()->json(['message'=>'Silinemedi'],404);
        }

      
    }
}

