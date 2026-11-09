import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { http } from '@/api/client';
import type { ListParams, PaginatedResponse, ResourceResponse } from '@/api/types';

export interface Uom {
  id: number;
  code: string;
  name: string;
  dimension: string;
}

export interface ProductCategory {
  id: number;
  parent_id: number | null;
  code: string;
  name: string;
  description: string | null;
}

export interface ProductComponentItem {
  id: number;
  parent_product_id: number;
  component_product_id: number;
  quantity: string;
  uom_id: number;
  component_role: string;
  component_product?: {
    id: number;
    code: string;
    name: string;
    product_type: string;
    physical_type: string;
    base_uom_id: number;
  };
  uom?: {
    id: number;
    code: string;
    name: string;
  };
}

export interface ProductItem {
  id: number;
  code: string;
  legacy_code: string | null;
  name: string;
  description: string | null;
  category_id: number | null;
  category?: { id: number; code: string; name: string } | null;
  product_type: 'PRODUCT' | 'SERVICE' | 'PROGRAM';
  physical_type: 'PHYSICAL' | 'DIGITAL' | 'NON_INVENTORY';
  fulfillment_type: 'SELL' | 'RENTAL' | 'INTERNAL' | 'MIXED';
  tracking_method: 'QUANTITY' | 'SERIALIZED' | 'BATCH' | 'NONE';
  base_uom_id: number;
  base_uom?: { id: number; code: string; name: string };
  safety_stock_qty: string;
  reference_purchase_price: string | null;
  is_manufactured: boolean;
  is_purchasable: boolean;
  is_sellable: boolean;
  is_rentable: boolean;
  status: string;
  available_qty?: string;
  on_hand_qty?: string;
  stock_health?: 'SAFE' | 'LOW_STOCK' | 'OUT_OF_STOCK' | 'NON_STOCK';
  components?: ProductComponentItem[];
  barcodes?: {
    id: number;
    barcode: string;
    barcode_type: string;
    is_primary: boolean;
  }[];
}

export interface WarehouseItem {
  id: number;
  code: string;
  name: string;
  warehouse_type: 'INTERNAL' | 'EXTERNAL_VENDOR' | 'CUSTOMER';
  address: string | null;
  is_active?: boolean;
  locations_count?: number;
}

export interface LocationItem {
  id: number;
  warehouse_id: number;
  parent_id: number | null;
  code: string;
  barcode: string | null;
  name: string;
  location_type: string;
  is_external?: boolean;
  is_active?: boolean;
  warehouse?: { id: number; code: string; name: string };
  parent?: { id: number; code: string; name: string } | null;
}

export interface PartnerItem {
  id: number;
  code: string;
  name: string;
  sales_name?: string | null;
  customer_type?: string;
  school_name?: string | null;
  vendor_type?: string;
  contact_name: string | null;
  phone: string | null;
  email?: string | null;
  is_active?: boolean;
}

export type CustomerItem = PartnerItem;

export function useCustomers() {
  return useQuery({
    queryKey: ['master-data', 'customers'],
    queryFn: async () => (await http.get<ResourceResponse<PartnerItem[]>>('/customers')).data.data,
  });
}

export function useProducts(params: ListParams) {
  return useQuery({
    queryKey: ['master-data', 'products', params],
    queryFn: async () => (await http.get<PaginatedResponse<ProductItem>>('/products', { params })).data,
    placeholderData: keepPreviousData,
  });
}

export function useCreateProduct() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: Record<string, unknown>) =>
      (await http.post<ResourceResponse<ProductItem>>('/products', payload)).data.data,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['master-data', 'products'] }),
  });
}

export function useCategories() {
  return useQuery({
    queryKey: ['master-data', 'categories'],
    queryFn: async () => (await http.get<ResourceResponse<ProductCategory[]>>('/categories')).data.data,
  });
}

export function useUoms() {
  return useQuery({
    queryKey: ['master-data', 'uoms'],
    queryFn: async () => (await http.get<ResourceResponse<Uom[]>>('/uoms')).data.data,
  });
}

export function useWarehouses() {
  return useQuery({
    queryKey: ['master-data', 'warehouses'],
    queryFn: async () => (await http.get<ResourceResponse<WarehouseItem[]>>('/warehouses')).data.data,
  });
}

export function useLocations(warehouseId?: number) {
  return useQuery({
    queryKey: ['master-data', 'locations', warehouseId],
    queryFn: async () =>
      (await http.get<ResourceResponse<LocationItem[]>>('/locations', { params: { warehouse_id: warehouseId } })).data.data,
  });
}

export function useCreateLocation() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: Record<string, unknown>) =>
      (await http.post<ResourceResponse<LocationItem>>('/locations', payload)).data.data,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['master-data', 'locations'] }),
  });
}

export function usePartners(kind: 'customers' | 'suppliers' | 'vendors') {
  return useQuery({
    queryKey: ['master-data', kind],
    queryFn: async () => (await http.get<ResourceResponse<PartnerItem[]>>(`/${kind}`)).data.data,
  });
}

export function useUpdateProduct() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, payload }: { id: number; payload: Record<string, unknown> }) =>
      (await http.put<ResourceResponse<ProductItem>>(`/products/${id}`, payload)).data.data,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['master-data', 'products'] });
    },
  });
}

export interface ProductStockBalance {
  id: number;
  location: { id: number; code: string; name: string } | null;
  warehouse: { id: number; code: string; name: string } | null;
  batch_number: string | null;
  serial_number: string | null;
  stock_status: string;
  on_hand_qty: string;
  reserved_qty: string;
  available_qty: string;
  avg_unit_cost: string | null;
}

export function useProductStock(productId: number | null) {
  return useQuery({
    queryKey: ['master-data', 'products', productId, 'stock'],
    enabled: productId !== null,
    queryFn: async () =>
      (await http.get<ResourceResponse<ProductStockBalance[]>>(`/products/${productId}/stock`)).data.data,
  });
}

export function useSyncComponents() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async ({
      productId,
      components,
    }: {
      productId: number;
      components: { component_product_id: number; quantity: number | string; uom_id: number; component_role?: string }[];
    }) =>
      (await http.post<ResourceResponse<ProductItem>>(`/products/${productId}/components`, { components })).data.data,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['master-data', 'products'] });
    },
  });
}

export interface BomProductDetail {
  product: { id: number; code: string; name: string };
  output_bom: {
    id: number;
    code: string;
    name: string;
    bom_type: string;
    stage_template?: { id: number; code: string; name: string } | null;
    active_version?: {
      id: number;
      version_no: number;
      status: string;
      items: {
        id: number;
        component_product_id: number;
        component: { id: number; code: string; name: string; base_uom_id: number; reference_purchase_price?: string | null };
        quantity: string;
        uom: { id: number; code: string; name: string };
        scrap_rate: string;
        notes?: string | null;
      }[];
    } | null;
  } | null;
  used_in_next_stages: {
    id: number;
    parent_bom_header_id: number;
    parent_bom_code: string;
    target_product: { id: number; code: string; name: string };
    stage_name: string;
    quantity: string;
    uom: { id: number; code: string; name: string };
    scrap_rate: string;
  }[];
}

export function useProductBom(productId: number | null) {
  return useQuery({
    queryKey: ['boms', 'by-product', productId],
    enabled: productId !== null,
    queryFn: async () =>
      (await http.get<ResourceResponse<BomProductDetail>>(`/boms/by-product/${productId}`)).data.data,
  });
}

export function useQuickSaveBom() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: {
      product_id: number;
      name?: string;
      items: { component_product_id: number; quantity: number | string; uom_id: number; scrap_rate?: number; notes?: string }[];
    }) =>
      (await http.post<ResourceResponse<any>>('/boms/quick-save', payload)).data.data,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['boms'] });
    },
  });
}

export function useAssembleStage() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: {
      product_id: number;
      quantity: number | string;
      source_location_id: number;
      destination_location_id: number;
      notes?: string;
    }) =>
      (await http.post<ResourceResponse<any>>('/boms/assemble-stage', payload)).data,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['master-data', 'products'] });
      qc.invalidateQueries({ queryKey: ['inventory'] });
    },
  });
}

export function useDeleteProduct() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (productId: number) =>
      (await http.delete(`/products/${productId}`)).data,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['master-data', 'products'] });
    },
  });
}

export function useCreateWarehouse() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: Record<string, unknown>) =>
      (await http.post<ResourceResponse<WarehouseItem>>('/warehouses', payload)).data.data,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['master-data', 'warehouses'] }),
  });
}

export function useUpdateWarehouse() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, payload }: { id: number; payload: Record<string, unknown> }) =>
      (await http.put<ResourceResponse<WarehouseItem>>(`/warehouses/${id}`, payload)).data.data,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['master-data', 'warehouses'] }),
  });
}

export function useDeleteWarehouse() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => (await http.delete(`/warehouses/${id}`)).data,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['master-data', 'warehouses'] }),
  });
}

export function useUpdateLocation() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, payload }: { id: number; payload: Record<string, unknown> }) =>
      (await http.put<ResourceResponse<LocationItem>>(`/locations/${id}`, payload)).data.data,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['master-data', 'locations'] }),
  });
}

export function useDeleteLocation() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => (await http.delete(`/locations/${id}`)).data,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['master-data', 'locations'] }),
  });
}

export function useCreateCustomer() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: Record<string, unknown>) =>
      (await http.post<ResourceResponse<PartnerItem>>('/customers', payload)).data.data,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['master-data', 'customers'] }),
  });
}

export function useUpdateCustomer() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, payload }: { id: number; payload: Record<string, unknown> }) =>
      (await http.put<ResourceResponse<PartnerItem>>(`/customers/${id}`, payload)).data.data,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['master-data', 'customers'] }),
  });
}

export function useDeleteCustomer() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => (await http.delete(`/customers/${id}`)).data,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['master-data', 'customers'] }),
  });
}

export interface DocumentTemplateItem {
  id: number;
  code: string;
  name: string;
  document_type: string;
  template_body: string;
  version: number;
  is_active: boolean;
  created_at?: string;
  updated_at?: string;
}

export function useDocumentTemplates() {
  return useQuery({
    queryKey: ['master-data', 'document-templates'],
    queryFn: async () => (await http.get<ResourceResponse<DocumentTemplateItem[]>>('/document-templates')).data.data,
  });
}

export function useLocationBalances(locationId?: number) {
  return useQuery({
    queryKey: ['inventory', 'balances', { location_id: locationId }],
    enabled: Boolean(locationId),
    queryFn: async () =>
      (await http.get<PaginatedResponse<any>>('/inventory/balances', {
        params: { location_id: locationId, per_page: 100, status: 'AVAILABLE' },
      })).data,
  });
}

export function useCreateDocumentTemplate() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: Record<string, unknown>) =>
      (await http.post<ResourceResponse<DocumentTemplateItem>>('/document-templates', payload)).data.data,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['master-data', 'document-templates'] }),
  });
}

export function useUpdateDocumentTemplate() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, payload }: { id: number; payload: Record<string, unknown> }) =>
      (await http.put<ResourceResponse<DocumentTemplateItem>>(`/document-templates/${id}`, payload)).data.data,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['master-data', 'document-templates'] }),
  });
}

export function useDeleteDocumentTemplate() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => (await http.delete(`/document-templates/${id}`)).data,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['master-data', 'document-templates'] }),
  });
}
