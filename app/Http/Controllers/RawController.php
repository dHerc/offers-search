<?php

namespace App\Http\Controllers;

use App\Services\EmbeddingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RawController
{
    public function getEmbeddings(Request $request)
    {
        return EmbeddingService::generateEmbeddings($request->query('text'));
    }

    public function compareEmbeddings(Request $request)
    {
        $base = [
            ["beach ball", "ball pen", "beach animal", "animal pen"],
            ["river bank", "bank of America", "river source", "source of America"],
            ["chocolate bar", "steel bar", "chocolate sculpture", "steel sculpture"],
            ["tree bark", "dog bark", "tree painting", "dog painting"],
            ["bat cave", "baseball bat", "game cave", "baseball game"],
            ["bowtie", "bowstring", "long tie", "long string"],
            ["book character", "text character", "book length", "text length"],
            ["treasure chest", "chest muscle", "big treasure", "big muscle"],
            ["start date", "cinema date", "start location", "cinema location"],
            ["dear parent", "dear antler", "old parent", "old antler"],
            ["university degree", "90 degree angle", "university dollar", "90 dollar angle"],
            ["cake dessert", "Sahara desert", "cake sun", "Sahara sun"],
            ["six-sided die", "blue die", "six-sided shape", "blue shape"],
            ["hard rock", "hard subject", "new rock", "new subject"],
            ["a just person", "just in time", "be a person", "be in time"],
            ["kind adult", "one of a kind", "adult group", "one of a group"],
            ["lead a pack", "lead pencil", "dollar pack", "dollar pencil"],
            ["leave as is", "falling leave", "behind as is", "falling behind"],
            ["to the left", "only one left", "to the end", "only one end"],
            ["handwritten letter", "letter of the alphabet", "handwritten part", "part of the alphabet"],
            ["light as a feather", "light in the dark", "walk as a feather", "walk in the dark"],
            ["mass-produced", "with high mass", "speed-produced", "with high speed"],
            ["burning match", "a perfect match", "burning tree", "a perfect tree"],
            ["first of May", "it may work", "first of all", "all work"],
            ["mean person", "mean value", "small person", "small value"],
            ["mint condition", "mint leaves", "good condition", "good leaves"],
            ["pestle and mortar", "brick and mortar", "pestle and things", "brick and things"],
            ["computer mouse", "mouse trap", "big computer", "big trap"],
            ["bowl of nuts", "screw nuts", "bowl of tips", "screw tips"],
            ["odd number", "odd person", "small number", "small person"],
            ["palm of the hand", "palm tree", "shake of the hand", "shake tree"],
            ["car park", "green park", "car color", "green color"],
            ["patient men", "hospital patient", "old man", "old hospital"],
            ["sheep pen", "ball pen", "sheep picture", "ball picture"],
            ["sales pitch", "baseball pitch", "sales position", "baseball position"],
            ["nail polish", "polish president", "old nail", "old president"],
            ["present day", "christmas present", "last day", "last christmas"],
            ["pretty pearson", "pretty please", "quick person", "quick please"],
            ["quarter past five", "quarter coin", "number past five", "number coin"],
            ["tennis racket", "made a racket", "tennis show", "made a show"],
            ["vinyl record", "criminal record", "vinyl shop", "criminal shop"],
            ["right turn", "right verdict", "good turn", "good verdict"],
            ["golden ring", "ring a bell", "friend ring", "ring a friend"],
            ["table row", "row the boat", "table top", "top of the boat"],
            ["city ruler", "school ruler", "city council", "school council"],
            ["second place", "split-second", "wood place", "wood split"],
            ["kitchen sink", "ship sink", "kitchen room", "ship room"],
            ["hard spirit", "human spirit", "hard pick", "human pick"],
            ["second story", "interesting story", "second chance", "interesting chance"],
            ["hand wave", "ocean wave", "big hand", "big ocean"],
        ];

        $data = [];
        foreach ($base as $value) {
            $dist1 = $this->compare($value[0], $value[1]);
            $dist2 = $this->compare($value[2], $value[3]);
            $data[] = [...$value, $dist1, $dist2];
        }
        usort($data, function ($first, $second) {
            return $first[4] > $second[4] ? 1 : -1;
        });
        $return = '';
        foreach ($data as $value) {
            $diff = number_format($value[5] - $value[4], 3);
            $first = number_format($value[4], 3);
            $second = number_format($value[5], 3);
            $return .= "$value[0] - $value[1]: $first --- $diff --- $second: $value[2] - $value[3]<br>";
        }
        return $return;
    }

    private function compare(string $first, string $second): float
    {
        $embeddings1 = '[' . EmbeddingService::generateEmbeddings($first) . ']';
        $embeddings2 = '[' . EmbeddingService::generateEmbeddings($second) . ']';
        return (float)DB::query()->selectRaw('?::vector <=> ? as distance', [$embeddings1, $embeddings2])->pluck('distance')[0];
    }
}
