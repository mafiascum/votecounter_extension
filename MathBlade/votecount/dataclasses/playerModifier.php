<?php

namespace MathBlade\votecount\dataclasses;

class playerModifier {


        protected $modifierName;
        protected $modifierType;
        protected $valueArray = array();
        protected $postNumberArray = array();
        protected $disabledOnLyloOrMylo;

        public const LOVED_MODIFIER_NAME = "loved";
        public const HATED_MODIFIER_NAME = "hated";
        public const TREESTUMP_MODIFIER_NAME = "treestump";

        public const BOOL_PLAYER_MODIFIER_TYPE_INT = 1;
        public const STRING_PLAYER_MODIFIER_TYPE_INT = 2;

        public function __construct($modifierName, $modifierType, $disabledOnLyloOrMylo)
        {
            $this->modifierName = $modifierName;
            $this->modifierType = $modifierType;
            $this->valueArray = array();
            $this->postNumberArray = array();
            $this->disabledOnLyloOrMylo = $disabledOnLyloOrMylo;

            if ($this->modifierType == BOOL_PLAYER_MODIFIER_TYPE_INT)
            {
              $this->addModifier(false,0);
            }
            else if  ($this->modifierType == STRING_PLAYER_MODIFIER_TYPE_INT) {
              $this->addModifier('',0);
            }

        }


        public static function all_valid_modifiers()
        {
            $validModifiers = array();
            array_push($validModifiers, playerModifier::buildBoolModifier(playerModifier::LOVED_MODIFIER_NAME, true));
            array_push($validModifiers, playerModifier::buildBoolModifier(playerModifier::HATED_MODIFIER_NAME, true));
            array_push($validModifiers, playerModifier::buildBoolModifier(playerModifier::TREESTUMP_MODIFIER_NAME, false));

            return $validModifiers;

        }

        public function getLyloMyloValue($currentPostNumber)
        {

            if ($this->disabledOnLyloOrMylo)
            {

              return $this->getDefaultValueOnLyloOrMylo();
            }
            else {
            
              return $this->getValueAtPostNumber($currentPostNumber);
            }
        }

        private function getValueIfNull()
        {

          $thisModifierType = $this->getType();
          switch($thisModifierType)
          {
              case playerModifier::BOOL_PLAYER_MODIFIER_TYPE_INT:

                return false;


              case playerModifier::BOOL_PLAYER_MODIFIER_TYPE_INT:
                return '';
              default:
                $errorString = $errorString . $modifierName . ' does not have a valid modifier type. Was given: ' . $thisModifierType . ' Please check for typos.';
                break;
          }

        }

        private function getDefaultValueOnLyloOrMylo()
        {
          $thisModifierType = $this->getType();
          switch($thisModifierType)
          {
              case playerModifier::BOOL_PLAYER_MODIFIER_TYPE_INT:

                return false;


              case playerModifier::BOOL_PLAYER_MODIFIER_TYPE_INT:
                return '';
              default:
                $errorString = $errorString . $modifierName . ' does not have a valid modifier type. Was given: ' . $thisModifierType . ' Please check for typos.';
                break;
          }

        }

        private static function buildBoolModifier($modifierName, $disabledOnLyloOrMylo)
        {
          return new playerModifier($modifierName,playerModifier::BOOL_PLAYER_MODIFIER_TYPE_INT, $disabledOnLyloOrMylo);
        }

        private static function buildStringModifier($modifierName, $disabledOnLyloOrMylo)
        {
          return new playerModifier($modifierName,playerModifier::STRING_PLAYER_MODIFIER_TYPE_INT, $disabledOnLyloOrMylo);
        }

        public function addModifierPost($value, $postNumberEffective)
        {

            $oldPostNumberArray = $this->getPostNumberArray();
            $oldValuesArray = $this->getValueArray();

            $newPostNumberArray = array();
            $newValueArray = array();
            $index = 0;
            $valueAdded = false;
            foreach ($oldPostNumberArray as $postNumberInArray)
            {
                if (!$valueAdded && ($postNumberEffective < $postNumberInArray))
                {
                    array_push($newPostNumberArray,$postNumberEffective);
                    array_push($newValueArray,$value);
                    $valueAdded = true;
                }
                array_push($newPostNumberArray,$postNumberInArray);
                array_push($newValueArray,$oldValuesArray[$index]);
                $index = $index +1;

            }

            if (!$valueAdded)
            {
              array_push($newPostNumberArray,$postNumberEffective);
              array_push($newValueArray,$value);
            }


            $this->setPostNumberArray($newPostNumberArray);
            $this->setValueArray($newValueArray);

        }

        public function getValueAtPostNumber($postNumber)
        {

            $index = 0;
            $previousPostNumber = -1;
            $valuesArray = $this->getValueArray();
            foreach($this->getPostNumberArray() as $modifierPostNumber)
            {

                if (($postNumber > $previousPostNumber) && ($postNumber <= $modifierPostNumber))
                {

                    return $valuesArray[$index];
                }
                else {

                  $index = $index + 1;
                  $previousPostNumber = $modifierPostNumber;
                }
            }


            $count = count($valuesArray);
            if ($count > 0)
            {

                return $valuesArray[$count -1];
            }
            else {

              return $this->getValueIfNull();
            }

        }

        public function getName()
        {
          return $this->modifierName;
        }
        public function getType()
        {
          return $this->modifierType;
        }

        public function getValueArray()
        {
          return $this->valueArray;
        }
        private function getPostNumberArray()
        {
          return $this->postNumberArray;
        }

        public function setPostNumberArray($array)
        {
          $this->postNumberArray = $array;
        }
        private function setValueArray($array)
        {
          $this->valueArray = $array;
        }


}
