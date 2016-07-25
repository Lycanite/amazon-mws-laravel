<?php namespace Peron\AmazonMwsFullfillment;

use Carbon\Carbon;

define('MARKETPLACE_ID', 'A1F83G8C2ARO7P');
set_include_path(get_include_path() . PATH_SEPARATOR . '../../.');

class AmazonFullfillment
{

    public function __construct($sellerID, $orderID, $itemIDs, $fromAddress, $packageType = "")
    {
        $keyId = 'AKIAJJ4YDI2AOAXEYTKQ';
        $secretId = '+1q9nOiULFChzTj6jUgeuM5wOvHAa+/JOnuBMaA8';

        $config = [
            'ServiceURL' => "https://mws-eu.amazonservices.com/MerchantFulfillment/2015-06-01",
            'ProxyHost' => null,
            'ProxyPort' => -1,
            'ProxyUsername' => null,
            'ProxyPassword' => null,
            'MaxErrorRetry' => 3,
        ];
        $this->service = new MWSMerchantFulfillmentService_Client(
            $keyId,
            $secretId,
            'Devilwear Hive',
            '1.0.0',
            $config
        );
        $this->sellerID = $sellerID;
        $this->orderID = $orderID;
        $this->itemIDs = $itemIDs;
        $this->fromAddress = $fromAddress;
        if($packageType != "")
            $this->setPredefinedDimensions($packageType, "460");
        else
            $this->setCustomDimensions("29", "17", "2", "460");
    }


    /**
	 *    Add Items - Adds a list of item entries.
     *    An item entry should be an associative array of "id" and "quantity".
	 **/
    public function addItems($itemEntries)
    {
        foreach($itemEntries as $itemEntry)
            $this->addItem($itemEntry);
    }


    /**
	 *    Add Item - Adds an individual item entry.
     *    An item entry should be an associative array of "id" and "quantity".
	 **/
    public function addItem($itemEntry)
    {
        if(!isset($this->items))
            $this->items = [];
        $item = new Item($itemEntry["id"], $itemEntry["quantity"]);
        $this->items[] = $item;
    }


    /**
	 *    Set Custom Dimensions - Sets the length, width height and weight of the package.
	 **/
    public function setCustomDimensions($length, $width, $height, $weight)
    {
        $this->dimensions = [
            'Length' => $length,
            'Width' => $width,
            'Height' => $height,
            'Unit' => "centimeters"
        ];
        $this->weight = [
            'Value' => $weight,
            'Unit' => 'grams'
        ];
    }

    /**
	 *    Set Predefined Dimensions - Sets the package type and weight of the package.
	 **/
    public function setPredefinedDimensions($packageType, $weight)
    {
       $this->dimensions = [
           'PredefinedPackageDimensions' => $packageType
       ];
       $this->weight = [
           'Value' => $weight,
           'Unit' => 'grams'
       ];
    }


    /**
	 *    Set Arrival Date - Sets the date that the package must arrive by.
	 **/
    public function setArrivalDate($date)
    {
        $this->arrivalDate = Carbon::fromString($date)->toIso8601String();
    }


    /**
	 *    Get Shipment Request Details - Returns shipment details for making requests.
	 **/
    public function getShipmentRequestDetails()
    {
        if(!isset($this->shipmentRequestDetails)) {
            $data = [];
            $data['AmazonOrderId'] = $this->orderID;
            $data['SellerOrderId'] = $this->orderID;
            $data['ItemList'] = $this->itemIDs;
            $data['ShipFromAddress'] = $this->fromAddress;
            $data['PackageDimensions'] = $this->dimensions;
            $data['Weight'] = $this->weight;
            if(isset($this->arrivalDate))
                $data['MustArriveByDate'] = $this->arrivalDate;
            $data['ShipDate'] = Carbon::now()->toIso8601String();
            $data['ShippingServiceOptions'] = [
                'DeliveryExperience' => "DeliveryConfirmationWithoutSignature", // DeliveryConfirmationWithoutSignature DeliveryConfirmationWithSignature
                'CarrierWillPickUp' => true
            ];
            $this->shipmentRequestDetails = new MWSMerchantFulfillmentService_Model_ShipmentRequestDetails($data);
        }
        return $this->shipmentRequestDetails;
    }


    /**
	 *    Get Shipping Services - Returns a list of available shipping services for the provided order details.
	 **/
    public function getShippingServices()
    {
        $request = new MWSMerchantFulfillmentService_Model_GetEligibleShippingServicesRequest();
        $request->setSellerId($this->sellerID);
        //$request->setMWSAuthToken();
        $request->setShipmentRequestDetails($this->getShipmentRequestDetails());
        return $this->service->GetEligibleShippingServices($request);
    }


    /**
	 *    Get Shipping Services - Returns a list of available shipping services for the provided order details.
	 **/
    public function createShipment($serviceID)
    {
        $request = new MWSMerchantFulfillmentService_Model_CreateShipmentRequest();
        $request->setSellerId($this->sellerID);
        $request->setShipmentRequestDetails($this->getShipmentRequestDetails());
        $request->setShippingServiceId($serviceID);
        return $this->service->CreateShipment($request);
    }
}
