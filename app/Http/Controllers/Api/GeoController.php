<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Geo;

class GeoController extends ApiController
{
    public function getByCode($code)
    {
        $geo = Geo::getCityByCode($code);
        if ($geo) {

            return $this->responseSuccess($geo);
        }

        $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }

    public function getMultipleByCode(Request $request)
    {
        $data = $request->all();
        $districts = Geo::getDistrictByCode($data);
        if ($districts) {

            return $this->responseSuccess($districts);
        }

        $this->sendError("Not Found", \Illuminate\Http\Response::HTTP_NOT_FOUND, "Not Found");
    }
}
