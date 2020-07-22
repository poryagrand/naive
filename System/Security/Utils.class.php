<?php

namespace System\Security;

use System\Controller\TemplateEngine\River;

class Utilities{
        /**
     * generate qr code in svg format
     */
    public static function generateQRCode($data,$color=0x000000){
        ob_start();
    
        // here DB request or some processing
        \QRcode::png((json_encode($data)), false, QR_ECLEVEL_L,15,3,true,0xffffff,$color||0x000000);
        
        // end of processing here
        $binary = ob_get_contents();
        ob_end_clean();
        return "data:image/png;base64,".\base64_encode($binary);
    }

    public static function convertToPDF($fileOrHtml,$share=[]){
        if( file_exists( $fileOrHtml ) ){
            $fileOrHtml = River::instance()->render($fileOrHtml);
        }

        if( file_exists( __APP__ . "/View/" . $fileOrHtml ) ){
            $fileOrHtml = River::instance()->render(__APP__ . "/View/" . $fileOrHtml,$share);
        }

        $mpdf = new \Mpdf\Mpdf(['tempDir' => __DIR__ . '/tmp']);
        $mpdf->WriteHTML($fileOrHtml);
        return $mpdf->Output();
    }

    public static function convertCSV($file,$model=null,$spliter=","){
        if( !file_exists($file) ){
            return false;
        }

        if( $model !== null && !is_subclass_of($model,CSVObject::class) ){
            return false;
        }

        $isSubset = false;
        if( $model !== null && is_subclass_of($model,CSVObject::class) ){
            $isSubset = true;
        }

        $output = [];
        if (($handle = fopen($file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 0, $spliter)) !== FALSE) {
                if( $isSubset ){
                    $res = forward_static_call_array([$model,"init"],$data);
                    if( $res !== null ){
                        $output[] = $res;
                    }
                }
                else{
                    $output[] = $data;
                }
            }
            fclose($handle);
        }
        return $output;
    }
}


class CSVObject{
    public static function init(){}
}