<?php

namespace App\Http\Resources\Api\V1\InvoiceResources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\V1\OrderResources\OrderForOrganizationResource;

class InvoiceForOrganizationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'invoice_code' => $this->invoice_code,

            'order_id' => $this->order_id,
            'organization_id' => $this->organization_id,

            'transaction_id_system' => $this->transaction_id_system,
            'transaction_id_banks' => $this->transaction_id_banks,

            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            
            'price_amount' => $this->price_amount,
            'status' => $this->status,
            'paid_date' => $this->paid_date,

            'payment_method' => $this->payment_method,
            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            

            'order' => OrderForOrganizationResource::make($this->whenLoaded('order', function () {
                return $this->order->load('contractDetail', 'vehicle');
            })),
            
        ];
    }
}
