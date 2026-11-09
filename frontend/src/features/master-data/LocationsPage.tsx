import { useState, useMemo } from 'react';
import {
  Building2,
  Edit2,
  GripVertical,
  MapPin,
  Move,
  Plus,
  RefreshCw,
  Trash2,
} from 'lucide-react';
import { Button } from '@/components/Button';
import { ConfirmationDialog } from '@/components/Modal';
import { PageHeader } from '@/components/PageHeader';
import { SearchBar } from '@/components/SearchBar';
import { StatusBadge } from '@/components/StatusBadge';
import { EmptyState, LoadingState } from '@/components/States';
import { useToast } from '@/components/Toast';
import { useAuth } from '@/features/auth/AuthContext';
import {
  useDeleteLocation,
  useDeleteWarehouse,
  useLocations,
  useUpdateLocation,
  useWarehouses,
  type LocationItem,
  type WarehouseItem,
} from './api';
import { LocationFormModal } from './LocationFormModal';
import { WarehouseFormModal } from './WarehouseFormModal';

export function LocationsPage() {
  const { can } = useAuth();
  const canManage = can('master_data.manage');
  const { notify } = useToast();

  const warehousesQuery = useWarehouses();
  const locationsQuery = useLocations();
  const updateLocation = useUpdateLocation();
  const deleteLocation = useDeleteLocation();
  const deleteWarehouse = useDeleteWarehouse();

  const [search, setSearch] = useState('');
  const [selectedWarehouseFilter, setSelectedWarehouseFilter] = useState<number | 'ALL'>('ALL');
  const [draggedItem, setDraggedItem] = useState<{ id: number; type: 'ROOM' | 'RACK'; name: string } | null>(null);

  // Modals state
  const [locationModal, setLocationModal] = useState<{
    open: boolean;
    data?: LocationItem | null;
    defaultWarehouseId?: number;
    defaultParentId?: number | null;
    defaultType?: string;
  }>({ open: false });

  const [warehouseModal, setWarehouseModal] = useState<{
    open: boolean;
    data?: WarehouseItem | null;
  }>({ open: false });

  const [deletingLocation, setDeletingLocation] = useState<LocationItem | null>(null);
  const [deletingWarehouse, setDeletingWarehouse] = useState<WarehouseItem | null>(null);

  // Group locations hierarchically
  const hierarchyData = useMemo(() => {
    const warehouses = warehousesQuery.data ?? [];
    const locations = locationsQuery.data ?? [];

    const locMap = new Map<number, LocationItem>();
    locations.forEach((loc) => locMap.set(loc.id, loc));

    // Filter by search if active
    const q = search.trim().toLowerCase();
    const matchesSearch = (loc: LocationItem) => {
      if (!q) return true;
      return (
        loc.code.toLowerCase().includes(q) ||
        loc.name.toLowerCase().includes(q) ||
        (loc.barcode && loc.barcode.toLowerCase().includes(q))
      );
    };

    return warehouses
      .filter((wh) => selectedWarehouseFilter === 'ALL' || wh.id === selectedWarehouseFilter)
      .map((wh) => {
        const whLocations = locations.filter((loc) => loc.warehouse_id === wh.id);
        
        // Floors (level 2)
        const floors = whLocations.filter((l) => l.location_type === 'FLOOR');
        
        // Structure: Floor -> Rooms -> Racks
        const structuredFloors = floors.map((floor) => {
          const rooms = whLocations.filter(
            (l) => l.location_type === 'ROOM' && l.parent_id === floor.id
          );

          const structuredRooms = rooms.map((room) => {
            const racks = whLocations.filter(
              (l) => (l.location_type === 'RACK' || l.location_type === 'BIN') && l.parent_id === room.id
            );
            return {
              room,
              racks: racks.filter(matchesSearch),
              matches: matchesSearch(room) || racks.some(matchesSearch),
            };
          });

          return {
            floor,
            rooms: structuredRooms.filter((r) => r.matches || matchesSearch(floor)),
            matches: matchesSearch(floor) || structuredRooms.some((r) => r.matches),
          };
        });

        // Other locations directly under warehouse or unparented
        const unparentedRooms = whLocations.filter(
          (l) => l.location_type === 'ROOM' && !floors.some((f) => f.id === l.parent_id)
        );

        return {
          warehouse: wh,
          floors: structuredFloors.filter((f) => f.matches || !q),
          unparentedRooms,
        };
      });
  }, [warehousesQuery.data, locationsQuery.data, selectedWarehouseFilter, search]);

  // Handle Drag & Drop move
  const handleDropRoom = async (roomId: number, targetFloorId: number) => {
    try {
      await updateLocation.mutateAsync({
        id: roomId,
        payload: {
          parent_id: targetFloorId,
        },
      });
      notify('Ruangan berhasil dipindahkan ke lantai baru.', 'success');
      setDraggedItem(null);
    } catch (err: any) {
      notify(err.message ?? 'Gagal memindahkan ruangan.', 'error');
    }
  };

  const handleDropRack = async (rackId: number, targetRoomId: number) => {
    try {
      await updateLocation.mutateAsync({
        id: rackId,
        payload: {
          parent_id: targetRoomId,
        },
      });
      notify('Rak berhasil dipindahkan ke ruangan baru.', 'success');
      setDraggedItem(null);
    } catch (err: any) {
      notify(err.message ?? 'Gagal memindahkan rak.', 'error');
    }
  };

  const handleDeleteLocation = async () => {
    if (!deletingLocation) return;
    try {
      await deleteLocation.mutateAsync(deletingLocation.id);
      notify(`Lokasi ${deletingLocation.code} berhasil dihapus.`, 'success');
      setDeletingLocation(null);
    } catch (err: any) {
      notify(err.message ?? 'Gagal menghapus lokasi. Pastikan tidak ada sub-lokasi atau saldo stok.', 'error');
    }
  };

  const handleDeleteWarehouse = async () => {
    if (!deletingWarehouse) return;
    try {
      await deleteWarehouse.mutateAsync(deletingWarehouse.id);
      notify(`Gedung ${deletingWarehouse.code} berhasil dihapus.`, 'success');
      setDeletingWarehouse(null);
    } catch (err: any) {
      notify(err.message ?? 'Gagal menghapus gedung. Pastikan gedung tidak memiliki data lokasi.', 'error');
    }
  };

  return (
    <>
      <PageHeader
        eyebrow="Master Data"
        title="Hierarki Lokasi Penyimpanan"
        description="Kelola tata letak fisik bertingkat: Gedung & Lantai (tetap), serta Ruangan & Rak (dapat di-drag & drop untuk penataan ulang lokasi)."
        actions={
          <div style={{ display: 'flex', gap: 8 }}>
            <Button
              icon={<RefreshCw size={15} />}
              onClick={() => {
                locationsQuery.refetch();
                warehousesQuery.refetch();
              }}
            >
              Muat Ulang
            </Button>
            {canManage && (
              <>
                <Button
                  variant="secondary"
                  icon={<Building2 size={15} />}
                  onClick={() => setWarehouseModal({ open: true, data: null })}
                >
                  Tambah Gedung
                </Button>
                <Button
                  variant="primary"
                  icon={<Plus size={15} />}
                  onClick={() => setLocationModal({ open: true, data: null })}
                >
                  Tambah Lokasi
                </Button>
              </>
            )}
          </div>
        }
      />

      {/* Info notice drag & drop */}
      <div
        style={{
          display: 'flex',
          alignItems: 'center',
          gap: 12,
          padding: '12px 16px',
          background: 'var(--color-primary-light, #eef3ff)',
          borderRadius: 8,
          border: '1px solid #c7d7f9',
          marginBottom: 16,
          fontSize: '0.875rem',
          color: '#263f7d',
        }}
      >
        <Move size={18} style={{ flexShrink: 0 }} />
        <div>
          <strong>Fitur Drag & Drop Interaktif:</strong> Gedung dan Lantai bersifat tetap. Ruangan dapat di-drag & drop ke Lantai lain, dan Rak dapat di-drag & drop ke Ruangan lain untuk memindahkan lokasi penyimpanan secara dinamis.
        </div>
      </div>

      {/* Toolbar filters */}
      <div className="card" style={{ marginBottom: 20 }}>
        <div className="toolbar">
          <SearchBar value={search} onChange={setSearch} placeholder="Cari kode lokasi, barcode, atau nama..." />
          <select
            className="select"
            style={{ width: 240 }}
            value={selectedWarehouseFilter}
            aria-label="Filter gedung"
            onChange={(e) => setSelectedWarehouseFilter(e.target.value === 'ALL' ? 'ALL' : Number(e.target.value))}
          >
            <option value="ALL">Semua Gedung / Warehouse</option>
            {warehousesQuery.data?.map((wh) => (
              <option key={wh.id} value={wh.id}>
                {wh.code} — {wh.name}
              </option>
            ))}
          </select>
        </div>
      </div>

      {locationsQuery.isLoading || warehousesQuery.isLoading ? (
        <LoadingState label="Memuat struktur hierarki lokasi..." />
      ) : hierarchyData.length === 0 ? (
        <EmptyState
          icon={<MapPin size={36} />}
          title="Belum ada data gedung & lokasi"
          description="Tambahkan gedung dan lokasi pertama untuk memulai pengelolaan inventaris."
          action={
            canManage ? (
              <Button variant="primary" icon={<Plus size={15} />} onClick={() => setWarehouseModal({ open: true, data: null })}>
                Tambah Gedung Sekarang
              </Button>
            ) : undefined
          }
        />
      ) : (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 24 }}>
          {hierarchyData.map(({ warehouse, floors }) => (
            <div
              key={warehouse.id}
              className="card"
              style={{
                border: '1px solid var(--color-border, #e1e8f5)',
                borderRadius: 'var(--radius-md, 12px)',
                overflow: 'hidden',
                padding: 0,
              }}
            >
              {/* Header Gedung */}
              <div
                style={{
                  display: 'flex',
                  justifyContent: 'space-between',
                  alignItems: 'center',
                  padding: '14px 20px',
                  background: 'var(--color-surface-muted, #f7f9fe)',
                  borderBottom: '1px solid var(--color-border, #e1e8f5)',
                }}
              >
                <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                  <Building2 size={20} color="var(--color-primary, #3859ac)" />
                  <div>
                    <span style={{ fontSize: '1rem', fontWeight: 700, color: 'var(--color-text-primary, #34466f)' }}>
                      Gedung: {warehouse.code} — {warehouse.name}
                    </span>
                    {warehouse.address && (
                      <span className="table__muted" style={{ marginLeft: 8, fontSize: '0.8rem' }}>
                        ({warehouse.address})
                      </span>
                    )}
                  </div>
                </div>

                {canManage && (
                  <div style={{ display: 'flex', gap: 4, alignItems: 'center' }}>
                    <Button
                      size="sm"
                      variant="secondary"
                      iconOnly
                      icon={<Plus size={15} />}
                      onClick={() =>
                        setLocationModal({
                          open: true,
                          data: null,
                          defaultWarehouseId: warehouse.id,
                          defaultType: 'FLOOR',
                        })
                      }
                      aria-label={`Tambah Lantai di ${warehouse.code}`}
                    />
                    <Button
                      size="sm"
                      variant="ghost"
                      iconOnly
                      icon={<Edit2 size={15} />}
                      onClick={() => setWarehouseModal({ open: true, data: warehouse })}
                      aria-label="Edit Gedung"
                    />
                    <Button
                      size="sm"
                      variant="ghost"
                      iconOnly
                      icon={<Trash2 size={15} color="var(--color-danger, #d96a36)" />}
                      onClick={() => setDeletingWarehouse(warehouse)}
                      aria-label="Hapus Gedung"
                    />
                  </div>
                )}
              </div>

              {/* Body: Lantai -> Ruangan -> Rak */}
              <div style={{ padding: '16px 20px' }}>
                {floors.length === 0 ? (
                  <div style={{ padding: '20px', textAlign: 'center', color: 'var(--color-text-secondary, #8191b1)' }}>
                    Belum ada data lantai di gedung ini. Klik ikon tambah (+) di samping gedung untuk menambahkan lantai.
                  </div>
                ) : (
                  <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
                    {floors.map(({ floor, rooms }) => (
                      <FloorDropZone
                        key={floor.id}
                        warehouse={warehouse}
                        floor={floor}
                        rooms={rooms}
                        canManage={canManage}
                        draggedItem={draggedItem}
                        setDraggedItem={setDraggedItem}
                        onDropRoom={handleDropRoom}
                        onDropRack={handleDropRack}
                        onAddLocation={(defaultParentId, defaultType) =>
                          setLocationModal({
                            open: true,
                            data: null,
                            defaultWarehouseId: warehouse.id,
                            defaultParentId,
                            defaultType,
                          })
                        }
                        onEditLocation={(loc) => setLocationModal({ open: true, data: loc })}
                        onDeleteLocation={(loc) => setDeletingLocation(loc)}
                      />
                    ))}
                  </div>
                )}
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Modal Tambah/Edit Lokasi */}
      {locationModal.open && (
        <LocationFormModal
          initialData={locationModal.data}
          defaultWarehouseId={locationModal.defaultWarehouseId}
          defaultParentId={locationModal.defaultParentId}
          defaultType={locationModal.defaultType}
          onClose={() => setLocationModal({ open: false })}
          onSuccess={() => locationsQuery.refetch()}
        />
      )}

      {/* Modal Tambah/Edit Gedung */}
      {warehouseModal.open && (
        <WarehouseFormModal
          initialData={warehouseModal.data}
          onClose={() => setWarehouseModal({ open: false })}
          onSuccess={() => {
            warehousesQuery.refetch();
            locationsQuery.refetch();
          }}
        />
      )}

      {/* Konfirmasi Hapus Lokasi */}
      <ConfirmationDialog
        open={Boolean(deletingLocation)}
        title="Hapus Lokasi"
        message={`Apakah Anda yakin ingin menghapus lokasi "${deletingLocation?.code} - ${deletingLocation?.name}"? Lokasi hanya dapat dihapus jika tidak memiliki anak/sub-lokasi dan tidak ada saldo stok fisik.`}
        confirmLabel="Hapus Lokasi"
        danger
        loading={deleteLocation.isPending}
        onConfirm={handleDeleteLocation}
        onCancel={() => setDeletingLocation(null)}
      />

      {/* Konfirmasi Hapus Gedung */}
      <ConfirmationDialog
        open={Boolean(deletingWarehouse)}
        title="Hapus Gedung / Warehouse"
        message={`Apakah Anda yakin ingin menghapus gedung "${deletingWarehouse?.code} - ${deletingWarehouse?.name}"? Gedung hanya dapat dihapus jika tidak memiliki data lokasi di dalamnya.`}
        confirmLabel="Hapus Gedung"
        danger
        loading={deleteWarehouse.isPending}
        onConfirm={handleDeleteWarehouse}
        onCancel={() => setDeletingWarehouse(null)}
      />
    </>
  );
}

// ==========================================
// Sub-Komponen: Floor Drop Zone (Lantai A)
// ==========================================
interface FloorDropZoneProps {
  warehouse: WarehouseItem;
  floor: LocationItem;
  rooms: { room: LocationItem; racks: LocationItem[] }[];
  canManage: boolean;
  draggedItem: { id: number; type: 'ROOM' | 'RACK'; name: string } | null;
  setDraggedItem: (item: { id: number; type: 'ROOM' | 'RACK'; name: string } | null) => void;
  onDropRoom: (roomId: number, floorId: number) => void;
  onDropRack: (rackId: number, roomId: number) => void;
  onAddLocation: (defaultParentId: number, defaultType: string) => void;
  onEditLocation: (loc: LocationItem) => void;
  onDeleteLocation: (loc: LocationItem) => void;
}

function FloorDropZone({
  warehouse,
  floor,
  rooms,
  canManage,
  draggedItem,
  setDraggedItem,
  onDropRoom,
  onDropRack,
  onAddLocation,
  onEditLocation,
  onDeleteLocation,
}: FloorDropZoneProps) {
  const [isOver, setIsOver] = useState(false);

  const isRoomDragging = draggedItem?.type === 'ROOM';

  return (
    <div
      style={{
        border: isOver && isRoomDragging ? '2px dashed #2563eb' : '1px solid #d8e2f3',
        backgroundColor: isOver && isRoomDragging ? '#eff6ff' : '#ffffff',
        borderRadius: 'var(--radius-sm, 8px)',
        overflow: 'hidden',
        transition: 'all 0.15s ease',
      }}
      onDragOver={(e) => {
        if (isRoomDragging) {
          e.preventDefault();
          e.dataTransfer.dropEffect = 'move';
          setIsOver(true);
        }
      }}
      onDragLeave={() => setIsOver(false)}
      onDrop={(e) => {
        e.preventDefault();
        setIsOver(false);
        try {
          const raw = e.dataTransfer.getData('application/json');
          if (raw) {
            const data = JSON.parse(raw);
            if (data.type === 'ROOM' && data.id) {
              onDropRoom(data.id, floor.id);
            }
          }
        } catch {}
      }}
    >
      {/* Bar Lantai: Gedung A - Lantai A */}
      <div
        style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          padding: '10px 16px',
          background: '#f8fafc',
          borderBottom: '1px solid #e2e8f0',
        }}
      >
        <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
          <span className="chip" style={{ background: '#e0f2fe', color: '#0369a1', fontWeight: 600 }}>
            Lantai
          </span>
          <span style={{ fontWeight: 600, color: 'var(--color-text-primary, #34466f)', fontSize: '0.925rem' }}>
            {warehouse.code} &mdash; {floor.name} (<code>{floor.code}</code>)
          </span>
          <StatusBadge status={floor.is_active !== false ? 'ACTIVE' : 'INACTIVE'} />
          {isOver && isRoomDragging && (
            <span style={{ fontSize: '0.8rem', color: '#2563eb', fontWeight: 600, marginLeft: 8 }}>
              &larr; Lepaskan untuk memindahkan Ruangan ke Lantai ini
            </span>
          )}
        </div>

        {canManage && (
          <div style={{ display: 'flex', gap: 4, alignItems: 'center' }}>
            <Button
              size="sm"
              variant="secondary"
              iconOnly
              icon={<Plus size={14} />}
              onClick={() => onAddLocation(floor.id, 'ROOM')}
              aria-label={`Tambah Ruangan di ${floor.code}`}
            />
            <Button
              size="sm"
              variant="ghost"
              iconOnly
              icon={<Edit2 size={14} />}
              onClick={() => onEditLocation(floor)}
              aria-label="Edit Lantai"
            />
            <Button
              size="sm"
              variant="ghost"
              iconOnly
              icon={<Trash2 size={14} color="var(--color-danger, #d96a36)" />}
              onClick={() => onDeleteLocation(floor)}
              aria-label="Hapus Lantai"
            />
          </div>
        )}
      </div>

      {/* Body: Daftar Ruangan di lantai ini */}
      <div style={{ padding: '12px 16px', display: 'flex', flexDirection: 'column', gap: 12 }}>
        {rooms.length === 0 ? (
          <div
            style={{
              padding: '16px',
              border: '1px dashed #cbd5e1',
              borderRadius: 6,
              textAlign: 'center',
              fontSize: '0.85rem',
              color: '#94a3b8',
            }}
          >
            Belum ada ruangan di lantai ini. Drag & drop ruangan ke sini atau klik ikon tambah (+) pada bar lantai.
          </div>
        ) : (
          rooms.map(({ room, racks }) => (
            <RoomDropZone
              key={room.id}
              floor={floor}
              room={room}
              racks={racks}
              canManage={canManage}
              draggedItem={draggedItem}
              setDraggedItem={setDraggedItem}
              onDropRack={onDropRack}
              onAddLocation={onAddLocation}
              onEditLocation={onEditLocation}
              onDeleteLocation={onDeleteLocation}
            />
          ))
        )}
      </div>
    </div>
  );
}

// ==========================================
// Sub-Komponen: Room Drop Zone & Draggable Room
// Lantai A - Ruangan A - Rak A
// ==========================================
interface RoomDropZoneProps {
  floor: LocationItem;
  room: LocationItem;
  racks: LocationItem[];
  canManage: boolean;
  draggedItem: { id: number; type: 'ROOM' | 'RACK'; name: string } | null;
  setDraggedItem: (item: { id: number; type: 'ROOM' | 'RACK'; name: string } | null) => void;
  onDropRack: (rackId: number, roomId: number) => void;
  onAddLocation: (defaultParentId: number, defaultType: string) => void;
  onEditLocation: (loc: LocationItem) => void;
  onDeleteLocation: (loc: LocationItem) => void;
}

function RoomDropZone({
  floor,
  room,
  racks,
  canManage,
  draggedItem,
  setDraggedItem,
  onDropRack,
  onAddLocation,
  onEditLocation,
  onDeleteLocation,
}: RoomDropZoneProps) {
  const [isOver, setIsOver] = useState(false);
  const isRackDragging = draggedItem?.type === 'RACK';
  const isCurrentRoomDragging = draggedItem?.type === 'ROOM' && draggedItem.id === room.id;

  return (
    <div
      style={{
        border: isOver && isRackDragging ? '2px dashed #059669' : '1px solid #e2e8f0',
        backgroundColor: isOver && isRackDragging ? '#f0fdf4' : isCurrentRoomDragging ? '#f1f5f9' : '#fafafa',
        borderRadius: 'var(--radius-sm, 8px)',
        opacity: isCurrentRoomDragging ? 0.45 : 1,
        transition: 'all 0.15s ease',
      }}
      onDragOver={(e) => {
        if (isRackDragging) {
          e.preventDefault();
          e.dataTransfer.dropEffect = 'move';
          setIsOver(true);
        }
      }}
      onDragLeave={() => setIsOver(false)}
      onDrop={(e) => {
        e.preventDefault();
        setIsOver(false);
        try {
          const raw = e.dataTransfer.getData('application/json');
          if (raw) {
            const data = JSON.parse(raw);
            if (data.type === 'RACK' && data.id) {
              onDropRack(data.id, room.id);
            }
          }
        } catch {}
      }}
    >
      {/* Header Ruangan: Lantai A - Ruangan A */}
      <div
        draggable={canManage}
        onDragStart={(e) => {
          setDraggedItem({ id: room.id, type: 'ROOM', name: room.name });
          e.dataTransfer.setData('application/json', JSON.stringify({ type: 'ROOM', id: room.id, name: room.name }));
        }}
        onDragEnd={() => setDraggedItem(null)}
        style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          padding: '8px 14px',
          background: '#ffffff',
          borderBottom: '1px solid #e2e8f0',
          cursor: canManage ? 'grab' : 'default',
        }}
      >
        <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
          {canManage && <GripVertical size={14} color="#94a3b8" style={{ cursor: 'grab' }} />}
          <span className="chip" style={{ background: '#dcfce7', color: '#15803d', fontWeight: 600 }}>
            Ruangan
          </span>
          <span style={{ fontWeight: 600, color: '#334155', fontSize: '0.875rem' }}>
            {floor.code} &mdash; {room.name} (<code>{room.code}</code>)
          </span>
          <StatusBadge status={room.is_active !== false ? 'ACTIVE' : 'INACTIVE'} />
          {isOver && isRackDragging && (
            <span style={{ fontSize: '0.775rem', color: '#059669', fontWeight: 600, marginLeft: 6 }}>
              &larr; Lepaskan untuk memindahkan Rak ke Ruangan ini
            </span>
          )}
        </div>

        {canManage && (
          <div style={{ display: 'flex', gap: 4, alignItems: 'center' }}>
            <Button
              size="sm"
              variant="secondary"
              iconOnly
              icon={<Plus size={13} />}
              onClick={(e) => {
                e.stopPropagation();
                onAddLocation(room.id, 'RACK');
              }}
              aria-label={`Tambah Rak di ${room.code}`}
            />
            <Button
              size="sm"
              variant="ghost"
              iconOnly
              icon={<Edit2 size={13} />}
              onClick={(e) => {
                e.stopPropagation();
                onEditLocation(room);
              }}
              aria-label="Edit Ruangan"
            />
            <Button
              size="sm"
              variant="ghost"
              iconOnly
              icon={<Trash2 size={13} color="var(--color-danger, #d96a36)" />}
              onClick={(e) => {
                e.stopPropagation();
                onDeleteLocation(room);
              }}
              aria-label="Hapus Ruangan"
            />
          </div>
        )}
      </div>

      {/* Body: Daftar Rak di ruangan ini: Lantai A - Ruangan A - Rak A */}
      <div style={{ padding: '8px 14px', display: 'flex', flexDirection: 'column', gap: 6 }}>
        {racks.length === 0 ? (
          <div style={{ padding: '8px 12px', fontSize: '0.8rem', color: '#94a3b8', fontStyle: 'italic' }}>
            Belum ada rak di ruangan ini. Drag & drop rak ke sini atau klik ikon tambah (+).
          </div>
        ) : (
          racks.map((rack) => (
            <DraggableRackItem
              key={rack.id}
              floor={floor}
              room={room}
              rack={rack}
              canManage={canManage}
              draggedItem={draggedItem}
              setDraggedItem={setDraggedItem}
              onEditLocation={onEditLocation}
              onDeleteLocation={onDeleteLocation}
            />
          ))
        )}
      </div>
    </div>
  );
}

// ==========================================
// Sub-Komponen: Draggable Rack Item
// Lantai A - Ruangan A - Rak A
// ==========================================
interface DraggableRackItemProps {
  floor: LocationItem;
  room: LocationItem;
  rack: LocationItem;
  canManage: boolean;
  draggedItem: { id: number; type: 'ROOM' | 'RACK'; name: string } | null;
  setDraggedItem: (item: { id: number; type: 'ROOM' | 'RACK'; name: string } | null) => void;
  onEditLocation: (loc: LocationItem) => void;
  onDeleteLocation: (loc: LocationItem) => void;
}

function DraggableRackItem({
  floor,
  room,
  rack,
  canManage,
  draggedItem,
  setDraggedItem,
  onEditLocation,
  onDeleteLocation,
}: DraggableRackItemProps) {
  const isCurrentRackDragging = draggedItem?.type === 'RACK' && draggedItem.id === rack.id;

  return (
    <div
      draggable={canManage}
      onDragStart={(e) => {
        setDraggedItem({ id: rack.id, type: 'RACK', name: rack.name });
        e.dataTransfer.setData('application/json', JSON.stringify({ type: 'RACK', id: rack.id, name: rack.name }));
      }}
      onDragEnd={() => setDraggedItem(null)}
      style={{
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        padding: '6px 12px',
        backgroundColor: '#ffffff',
        border: '1px solid #cbd5e1',
        borderRadius: 6,
        cursor: canManage ? 'grab' : 'default',
        opacity: isCurrentRackDragging ? 0.4 : 1,
      }}
    >
      <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
        {canManage && <GripVertical size={13} color="#94a3b8" />}
        <span className="chip" style={{ background: '#fef3c7', color: '#b45309', fontSize: '0.75rem', fontWeight: 600 }}>
          Rak
        </span>
        <span style={{ fontSize: '0.85rem', color: '#1e293b' }}>
          <strong>{floor.code} &mdash; {room.code} &mdash; {rack.name}</strong> (<code>{rack.code}</code>)
        </span>
        {rack.barcode && (
          <span className="table__muted" style={{ fontSize: '0.75rem' }}>
            Barcode: {rack.barcode}
          </span>
        )}
      </div>

      {canManage && (
        <div style={{ display: 'flex', gap: 2, alignItems: 'center' }}>
          <Button
            size="sm"
            variant="ghost"
            iconOnly
            icon={<Edit2 size={13} />}
            onClick={() => onEditLocation(rack)}
            aria-label="Edit Rak"
          />
          <Button
            size="sm"
            variant="ghost"
            iconOnly
            icon={<Trash2 size={13} color="var(--color-danger, #d96a36)" />}
            onClick={() => onDeleteLocation(rack)}
            aria-label="Hapus Rak"
          />
        </div>
      )}
    </div>
  );
}
