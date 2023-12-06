<section class="brands-carousel">    
    <h2 class="sr-only">Brands Carousel </h2>
    <div class="col-full" data-ride="tm-slick-carousel" data-wrap=".brands" data-slick="{&quot;slidesToShow&quot;:6,&quot;slidesToScroll&quot;:1,&quot;dots&quot;:false,&quot;arrows&quot;:true,&quot;responsive&quot;:[{&quot;breakpoint&quot;:400,&quot;settings&quot;:{&quot;slidesToShow&quot;:1,&quot;slidesToScroll&quot;:1}},{&quot;breakpoint&quot;:800,&quot;settings&quot;:{&quot;slidesToShow&quot;:3,&quot;slidesToScroll&quot;:3}},{&quot;breakpoint&quot;:992,&quot;settings&quot;:{&quot;slidesToShow&quot;:3,&quot;slidesToScroll&quot;:3}},{&quot;breakpoint&quot;:1200,&quot;settings&quot;:{&quot;slidesToShow&quot;:4,&quot;slidesToScroll&quot;:4}},{&quot;breakpoint&quot;:1400,&quot;settings&quot;:{&quot;slidesToShow&quot;:5,&quot;slidesToScroll&quot;:5}}]}">
        <div class="brands">
        <?php        
        if(is_array($brands_list)){
            foreach ($brands_list as $brand) {
        ?>
        <div class="item">
            <a href="<?= base_url("webshop/products/?q=brand&catid=".$brand->category_id."&key=".str_replace([' & ', '&',' ','-'], '_', $brand->brand_name)."&id=".md5($brand->brand_id))?>">
                <figure>
                    <figcaption class="text-overlay">
                        <div class="info">
                            <h4><?=$brand->brand_name?></h4>
                        </div>
                        <!-- /.info -->
                    </figcaption>
                    <img style="max-height: 80px;" class="img-responsive desaturate" alt="<?=$brand->brand_name?>" src="<?=$uploads . $brand->brand_image?>">
                </figure>
            </a>
        </div>   
            
        <?php
            }//end foreach.
        }        
        ?>
            
        </div>
    </div>
    <!-- .col-full -->
</section>
<!-- .brands-carousel -->