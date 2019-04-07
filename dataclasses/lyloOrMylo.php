<?php

namespace mafiascum\votecounter_extension\dataclasses;


class lyloOrMylo {


        protected $postNumber;
        protected $isLyloOrMylo;

        public function __construct($postNumber,$isLyloOrMylo)
        {
          $this->postNumber = $postNumber;
          $this->isLyloOrMylo = ($isLyloOrMylo === 'true');

        }

        public function getPostNumber()
        {
          return $this->postNumber;
        }

        public function getIsLyloOrMylo()
        {
          return $this->isLyloOrMylo;
        }

        private static function compareLyloMylos(&$lyloA, &$lyloB)
        {
            return strcasecmp($lyloA->getPostNumber(), $lyloB->getPostNumber());
        }

        public static function IsLyloOrMylo($lyloOrMyloArray,$postNumber)
        {

          if (!is_array($lyloOrMyloArray))
          {
            return false;
          }
          else if (count($lyloOrMyloArray) == 0)
          {
            return false;
          }

          $isLyloOrMylo = false;
          usort($lyloOrMyloArray,['\mafiascum\votecounter_extension\dataclasses\lyloOrMylo', 'compareLyloMylos']);
          foreach($lyloOrMyloArray as $lyloOrMylo)
          {
              if ($lyloOrMylo->getPostNumber() < $postNumber)
              {
                  $isLyloOrMylo = $lyloOrMylo->getIsLyloOrMylo();
              }
          }

          return $isLyloOrMylo;
        }

        public static function build_initial_array()
        {
          $initialArray = array();
          array_push($initialArray, new lyloOrMylo(0,false));
          return $initialArray;
        }





}
