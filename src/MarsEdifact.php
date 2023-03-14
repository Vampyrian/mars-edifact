<?php

namespace MarsEditact;

use EDI\Parser;
use EDI\Reader;

class MarsEdifact
{
    public function getJsonFromEdifactMessage($rawMsg)
    {
        $parser = new Parser();
        $parser->loadString($rawMsg);
        $array = $parser->get();
        return json_encode($array);
    }

    public function accept($rawMsg)
    {
        return $this->handleLoad($rawMsg, '98');
    }

    public function decline($rawMsg)
    {
        return $this->handleLoad($rawMsg, '99');
    }

    private function handleLoad($rawMsg, $stsCode)
    {
        $reader = new Reader($rawMsg);
        $code = $reader->readEdiDataValue('UNB', 5);

        $msgNr = (string) ($reader->readUNHmessageNumber() + 1);
        $receiver = $reader->readUNBInterchangeSender();
        $sender = $reader->readUNBInterchangeRecipient();
        $documentNr = $reader->readEdiDataValue('BGM', 2);
        $toNr = $reader->readEdiDataValue('CNI', 2);

        $ediMsgArray = [];
        $ediMsgArray[] = ["UNB", ["UNOY", "3"], $sender, $receiver, [date("ymd"), date("Hi")], $code];
        $ediMsgArray[] = ["UNH", $msgNr, ["IFTSTA", "D", "01B", "UN"]];
        $ediMsgArray[] = ["BGM", "7", $documentNr, "9"];
        $ediMsgArray[] = ["DTM", ["137", date("YmdHis"), "204"]];
        $ediMsgArray[] = ["NAD", "FP", "15066960", "87"];
        $ediMsgArray[] = ["CNI", "1", $toNr];
        $ediMsgArray[] = ["STS", "1", $stsCode];
        $ediMsgArray[] = ["REF", ["CU", $documentNr]];
        $ediMsgArray[] = ["REF", ["SRN", $toNr]];
        $ediMsgArray[] = ["UNT", "8", "1"];
        $ediMsgArray[] = ["UNZ", "1", $code];

        $encodedString = new \EDI\Encoder($ediMsgArray, true);
        return 'UNA:+.? \'' . $encodedString->get();
    }


}
