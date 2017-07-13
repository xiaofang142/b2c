/*
 * 云分销商城
 * 
 * @author 三思云科技 
 */
define(['jquery','core'], function($,core){
    var shop = {
        category: { }
    };
    //获取店铺分类
    shop.getCategory = function(callback){
             core.json('shop/util/category',{},function(ret){
              shop.category = ret;
              if(callback){
                  callback(ret);
              }
           });
    }
    return shop;
});

