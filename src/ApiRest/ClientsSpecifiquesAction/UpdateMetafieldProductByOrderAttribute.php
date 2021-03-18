<?php
namespace Wbu\ApiRest\ClientsSpecifiquesAction;
use Wbu\ApiRest\WbuShopify;

class UpdateMetafieldProductByOrderAttribute
{
    private $configs = null;
    protected $attributes = null;
    protected $id_product = null;
    protected $text = [];
    /**
     * conteint les données du  metafield;
     * @var array
     */
    protected $selection_plage_heure = [];

    /**
     * permet de definir le nombre de reservation possible en journée.
     * @var integer
     */
    protected $nombre_car=1;
    /**
     * Heure à partir de laquelle on considere que la matiné est terminé.
     * @var integer
     */
    public $heure_max_matinee = 14;

    function __construct($configs, $attributes, $id_product) {
        $this->attributes = $attributes;
        $this->configs = $configs;
        $this->id_product = $id_product;
    }

    protected function transformStringToArray(){
        $attributes=[];
        foreach ($this->attributes as $key=>$value) {
            $element=[];
            $value = explode("\n", $value);
            if(!empty($value[1])){
                $date = explode(" : ", $value[1]);
                $element['date'] = trim($date[1]);
            }
            if(!empty($value[2])){
                $heure = explode(" : ", $value[2]);
                $heure = explode(":", $heure[1]);
                $element['ht_debut'] = intval($heure[0]);
                $element['mn_debut'] = intval($heure[1]);
            }
            $attributes[$key] = $element;
        }
        return $attributes;
    }

    /**
     *
     */
    protected function load_selection_plage_heure(){
        $_WbuShopify = new WbuShopify( $this->configs );
        $url = '/admin/api/2019-10/products/' . $this->id_product . '/metafields.json';
        $metafields = $_WbuShopify->LoadMetafieldArticle( $url );
        if(!empty($metafields['metafields'])){
            foreach ($metafields['metafields'] as $metafield){
                if( !empty($metafield['namespace']) && $metafield['namespace'] == $_WbuShopify->namespace && $metafield['key'] == 'selection_plage_heure' ){
                    $this->selection_plage_heure = json_decode($metafield['value'], true);
                    $this->text['selection_plage_heure_defaut'][] = $this->selection_plage_heure;
                    return true;
                }
            }
        }
        return false;
    }

    /**
     *  On parcourt les dates dans selection_plage_heure si on rencontre la meme date, on ignore.
     */
    protected function CheckValues(){
        $attributes = $this->transformStringToArray();
        $save_status=false;
        $add_recuperation=true;
        $add_livraison=true;
        if(!empty($this->selection_plage_heure)){
            $type_selection = 'recuperation';
            foreach ( $this->selection_plage_heure[$type_selection] as $key=>$value){
                if(!isset($value['mn_debut'])){
                    $value['mn_debut'] = 0;
                }
                if(
                    ($attributes[$type_selection]['date'] == $value['date']) &&
                    ($attributes[$type_selection]['ht_debut'] == $value['ht_debut']) &&
                    ($attributes[$type_selection]['mn_debut'] == $value['mn_debut'])
                ){
                    $add_recuperation=false;
                    //$this->AddNewValue( $type_selection, $attributes[$type_selection] );
                    //$save_status=true;
                    //$this->text['date_identique'][] = ['key'=>$key, 'type_selection'=>$type_selection, 'attributes'=>$attributes[$type_selection]];
                }
                elseif(
                    ($attributes[$type_selection]['date'] == $value['date']) &&
                    ($attributes[$type_selection]['ht_debut'] != $value['ht_debut'])
                    //( count($this->selection_plage_heure[$type_selection]) < $this->nombre_car)
                    ){
                        //$add_recuperation=false;
                }else{
                    $this->deleteDate($type_selection, $key, $value['date']);
                }
            }
            if($add_recuperation){
                $this->AddNewValue( $type_selection, $attributes[$type_selection] );
                $this->text['AddNewValue-'.$type_selection][] = ['key'=>$key, 'type_selection'=>$type_selection, 'attributes'=>$attributes[$type_selection]];
                $save_status=true;
            }
            $type_selection = 'livraison';
            foreach ( $this->selection_plage_heure[$type_selection] as $key=>$value){
                if(!isset($value['mn_debut'])){
                    $value['mn_debut'] = 0;
                }
                if(
                    ($attributes[$type_selection]['date'] == $value['date']) &&
                    ($attributes[$type_selection]['ht_debut'] == $value['ht_debut']) &&
                    ($attributes[$type_selection]['mn_debut'] == $value['mn_debut'])
                    ){
                        $add_livraison=false;
                        //$this->AddNewValue( $type_selection, $attributes[$type_selection] );
                        //$save_status=true;
                        //$this->text['date_identique'][] = ['key'=>$key, 'type_selection'=>$type_selection, 'attributes'=>$attributes[$type_selection]];
                }
                elseif(
                    ($attributes[$type_selection]['date'] == $value['date']) &&
                    ($attributes[$type_selection]['ht_debut'] != $value['ht_debut'])
                    //( count($this->selection_plage_heure[$type_selection]) < $this->nombre_car)
                    ){
                        //$add_livraison=false;
                }else{
                    $this->deleteDate($type_selection, $key, $value['date']);
                }
            }
            if($add_livraison){
                $this->AddNewValue( $type_selection, $attributes[$type_selection] );
                $this->text['AddNewValue-'.$type_selection][] = ['key'=>$key, 'type_selection'=>$type_selection, 'attributes'=>$attributes[$type_selection]];
                $save_status=true;
            }
        }else{
            foreach ($attributes as $type_selection => $values) {
                $this->AddNewValue( $type_selection, $values );
                $save_status=true;
            }
        }
        return $save_status;
    }

    /**
     *
     */
    public function deleteDate($type_selection, $key, $date){
        $passDate = strtotime(str_replace("/","-", $date));
        $currentHow = strtotime('now');
        if( $currentHow > ($passDate + 60*60*24) ){
            unset($this->selection_plage_heure[$type_selection][$key]);
        }
    }

    /**
     *
     */
    protected function AddNewValue($type_selection, $values){
        $this->selection_plage_heure[$type_selection][] = $values;
        $this->text['selection_plage_heure_add'][] = $this->selection_plage_heure;
    }

    /**
     *
     */
    protected function saveMetafield(){
        $_WbuShopify = new WbuShopify( $this->configs );
        return $_WbuShopify->SaveMetafieldProduct( $this->id_product, 'selection_plage_heure', $this->selection_plage_heure );
    }

    /**
     *
     */
    public function buildAction(){
        $this->load_selection_plage_heure();
        if($this->CheckValues() && !empty($this->selection_plage_heure)){
            return [$this->saveMetafield(), $this->text, $this->attributes];
        }
        return ['Error', $this->selection_plage_heure, $this->text, $this->attributes];
    }


}

/*
 *
 "livraison
 Date : 14/11/2019
 Heure : 09:00 - 10:00
"

"recuperation
 Date : 12/11/2019
 Heure : 20:30 - 21:30
"

 Model pour le metafield
 const selection_plage_heure =
 {
 recuperation: [
     {
         type:'recuperation',
         date:'9/11/2019',
         ht_debut:10,
         ht_fin:12,
         mn:30,
         periode_journee:'matinee',
     }
    ],
 livraison: [
    {
         type:'livraison',
         date:'12/11/2019',
         ht_debut:8,
         ht_fin:10,
         mn:0,
         periode_journee:'matinee',
     }
    ]
 }
 ;
 */