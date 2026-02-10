<?php

namespace App\Services;

use SimpleXMLElement;

class XmlService
{
    public static function toXml(object $dto, string $rootName = 'Invoice'): string
    {
        $xml = new SimpleXMLElement("<{$rootName}/>");
        self::addObjectToXml($dto, $xml);
        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        return $dom->saveXML();
    }

    protected static function addObjectToXml(object $obj, SimpleXMLElement $xml)
    {
        foreach ($obj as $key => $value) {
            if ($value === null) {
                continue; // Preskoči null vrijednosti
            }

            if (is_object($value)) {
                if ($value instanceof \BackedEnum) {
                    $xml->addChild($key, $value->value);
                } else {
                    $child = $xml->addChild($key);
                    self::addObjectToXml($value, $child);
                }
            } elseif (is_array($value)) {
                if ($key === 'items') {
                    $itemsNode = $xml->addChild('items');
                    foreach ($value as $item) {
                        $itemNode = $itemsNode->addChild('Item');
                        self::addObjectToXml($item, $itemNode);
                    }
                } else {
                    $arrayNode = $xml->addChild($key);
                    foreach ($value as $item) {
                        if (is_object($item)) {
                            $childNode = $arrayNode->addChild('Child');
                            self::addObjectToXml($item, $childNode);
                        } else {
                            $arrayNode->addChild('Value', $item);
                        }
                    }
                }
            } else {
                $xml->addChild($key, htmlspecialchars((string)$value));
            }
        }
    }
}
