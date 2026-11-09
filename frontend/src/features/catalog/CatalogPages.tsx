import { CatalogListPage } from './CatalogListPage';

export function ComponentsPage() {
  return (
    <CatalogListPage
      eyebrow="Katalog — Bahan Baku"
      title="Komponen"
      description="Kelola data komponen/spare part dan pantau ketersediaan stok fisik di rak gudang."
      itemGroup="component"
      categoryCode="COMP"
      defaultCategoryCode="COMP"
      defaultProductType="PRODUCT"
      defaultPhysicalType="PHYSICAL"
      defaultTrackingMethod="QUANTITY"
      nextStageTitle="WIP Modul / Box"
    />
  );
}

export function ModulesPage() {
  return (
    <CatalogListPage
      eyebrow="Katalog — Subassembly"
      title="WIP Modul"
      description="Kelola subassembly modul elektronik & mekanik (M01, M02), pantau stok hasil perakitan, dan ubah resep BOM ke tahap selanjutnya sewaktu-waktu."
      itemGroup="module"
      categoryCode="WIP-MOD"
      defaultCategoryCode="WIP-MOD"
      defaultProductType="PRODUCT"
      defaultPhysicalType="PHYSICAL"
      defaultTrackingMethod="QUANTITY"
      nextStageTitle="WIP Box"
      previousStageTitle="Komponen"
    />
  );
}

export function BoxesPage() {
  return (
    <CatalogListPage
      eyebrow="Katalog — Kit Wadah / Kemasan"
      title="WIP Box"
      description="Kelola kit box pembelajaran (B01, B02), pantau stok integrasi modul ke dalam box, dan atur BOM menuju Finished Good."
      itemGroup="box"
      categoryCode="WIP-BOX"
      defaultCategoryCode="WIP-BOX"
      defaultProductType="PRODUCT"
      defaultPhysicalType="PHYSICAL"
      defaultTrackingMethod="QUANTITY"
      nextStageTitle="Finished Good MLK"
      previousStageTitle="WIP Modul & Komponen"
    />
  );
}

export function FinishedGoodsPage() {
  return (
    <CatalogListPage
      eyebrow="Katalog — Produk Akhir & Layanan"
      title="Finished Good & Layanan"
      description="Kelola produk siap jual atau sewa (seperti MLK Standar dengan serial number) dan produk jasa/service instruktur, beserta resep BOM perakitannya."
      itemGroup="finished_good"
      categoryCode="FG,SRV"
      defaultCategoryCode="FG"
      defaultProductType="PRODUCT"
      defaultPhysicalType="PHYSICAL"
      defaultTrackingMethod="SERIALIZED"
      nextStageTitle="Distribusi / Sekolah"
      previousStageTitle="WIP Box & Modul"
    />
  );
}
