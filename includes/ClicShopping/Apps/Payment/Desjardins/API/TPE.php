<?php
  /**
 *
 *  @copyright 2008 - https://www.clicshopping.org
 *  @Brand : ClicShopping(Tm) at Inpi all right Reserved
 *  @Licence GPL 2 & MIT
 *  @licence MIT - Portion of osCommerce 2.4
 *  @Info : https://www.clicshopping.org/forum/trademark/
 *
 */

  namespace ClicShopping\Apps\Payment\Desjardins\API;

  use ClicShopping\OM\CLICSHOPPING;

  class TPE  {

    public $sVersion;  // Version du TPE - EPT Version (Ex : 3.0)
    public $sNumero; // Numero du TPE - EPT Number (Ex : 1234567)
    public $sCodeSociete;  // Code Societe - Company code (Ex : companyname)
    public $sLangue; // Langue - Language (Ex : FR, DE, EN, ..)

    private $_sCle;   // La clef - The Key


    function __construct($sLangue = 'FR') {

  // controle de l'existence des constantes de parametrages.
      $aRequiredConstants = ['CLICSHOPPING_APP_DESJARDINS_HO_KEY',
                              'CLICSHOPPING_APP_DESJARDINS_HO_VERSION',
                              'CLICSHOPPING_APP_DESJARDINS_HO_TPE',
                              'CLICSHOPPING_APP_DESJARDINS_HO_COMPANY'
                            ];

      $this->_checkEptParams($aRequiredConstants);
      $this->sVersion = CLICSHOPPING_APP_DESJARDINS_HO_VERSION;
      $this->_sCle = CLICSHOPPING_APP_DESJARDINS_HO_KEY;
      $this->sNumero = CLICSHOPPING_APP_DESJARDINS_HO_TPE;
      $this->sCodeSociete = CLICSHOPPING_APP_DESJARDINS_HO_COMPANY;
      $this->sLangue = $sLangue;
    }

    // ----------------------------------------------------------------------------
    //
    // Fonction / Function : getCle
    //
    // Renvoie la clé du TPE / return the EPT Key
    //
    // ----------------------------------------------------------------------------

    public function getCle() {
      return $this->_sCle;
    }

    // ----------------------------------------------------------------------------
    //
    // Fonction / Function : _checkEptParams
    //
    // Contrôle l'existence des constantes d'initialisation du TPE
    // Check for the initialising constants of the EPT
    //
    // ----------------------------------------------------------------------------

    private function _checkEptParams($aConstants) {

      for ($i = 0; $i < count($aConstants); $i++)
        if (!defined($aConstants[$i]))
          die ("Erreur parametre " . $aConstants[$i] . " indefini");
    }


    public function getUnserializeData($serialstring) {

      if(preg_match_all('/(\w+)\|(.*?)(?=(\w+)\||$)/', $serialstring, $matches)){

        foreach ($matches[1] as $i=>$k){
          //var_dump("$k");
          $v=$matches[2][$i];  // post-pipe group
          if(preg_match_all('/s:(\d+):"([^"]*?)"/',$v,$matches2)){ // capture string lengths and values
            //print_r($matches2);

            foreach ($matches2[1] as $i=>$len) {
              if(($newlen = strlen($matches2[2][$i]))!=$len){  // if bad string length count, fix it
                $v=str_replace("s:{$len}:\"{$matches2[2][$i]}\"","s:{$newlen}:\"{$matches2[2][$i]}\"",$v);
              }
            }
          }

          if(substr_count($v,"{")>substr_count($v,"}")){ // if insufficient closing curly brackets, fix it
            $v.= str_repeat("}",substr_count($v,"{")-substr_count($v,"}"));
          }

          if(!in_array(substr($v,-1),[";","}"])){
            $v.= ';'; // append semicolon where not ending in } or ;
          }
          //var_dump($v);
          //echo "\n";
          if ($v == "N;"){
            $result[$k] = null;  // this is a workaround for an inexplicable unserialize() failure
          } elseif($v == 's:0:"";') {
            $result[$k] = '';  // this is a workaround for an inexplicable unserialize() failure
          } elseif($unserial = unserialize($v, ['allowed_classes' => false])) {
            $result[$k] = $unserial;
          } else{
            echo 'There was an error unserializing' . $k ."\n";
            var_dump($v);
          }
        }
      }

      return $result;
    }
  }
