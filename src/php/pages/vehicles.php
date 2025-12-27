<?php
/**
 * Vehicles Page (Read-Only)
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../datastore.php';

Auth::requireAuth();

$vehicles = DataStore::getVehicles();
$locations = DataStore::getLocations();
?>

<div class="card">
    <div class="card-header">
        <span>Fahrzeuge</span>
    </div>
    <div class="card-content">
        <!-- Search and Filter Controls -->
        <div class="filter-controls" style="margin-bottom: 1rem; display: flex; gap: 1rem; flex-wrap: wrap;">
            <div class="form-group" style="flex: 1; min-width: 200px; margin: 0;">
                <input type="text" id="searchVehicles" class="form-input" placeholder="Suchen nach Typ oder Funkrufname...">
            </div>
            <div class="form-group" style="flex: 0 0 150px; margin: 0;">
                <select id="filterLocation" class="form-input">
                    <option value="">Alle Standorte</option>
                    <?php
                    foreach ($locations as $location):
                    ?>
                    <option value="<?php echo htmlspecialchars($location['name']); ?>"><?php echo htmlspecialchars($location['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="flex: 0 0 150px; margin: 0;">
                <select id="filterType" class="form-input">
                    <option value="">Alle Typen</option>
                    <?php
                    $types = array_unique(array_filter(array_column($vehicles, 'type')));
                    sort($types);
                    foreach ($types as $type):
                    ?>
                    <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <?php if (empty($vehicles)): ?>
            <p class="text-center" style="color: var(--text-secondary);">Keine Fahrzeuge vorhanden.</p>
        <?php else: ?>
            <div class="table-container">
                <table class="table" id="vehiclesTable">
                    <thead>
                        <tr>
                            <th onclick="sortVehicles('type')" style="cursor: pointer;">
                                Typ <span class="material-icons" style="font-size: 16px; vertical-align: middle;">unfold_more</span>
                            </th>
                            <th onclick="sortVehicles('location')" style="cursor: pointer;">
                                Ort/Standort <span class="material-icons" style="font-size: 16px; vertical-align: middle;">unfold_more</span>
                            </th>
                            <th onclick="sortVehicles('radio_call_sign')" style="cursor: pointer;">
                                Funkrufname <span class="material-icons" style="font-size: 16px; vertical-align: middle;">unfold_more</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="vehiclesTableBody">
                        <?php
                        // Sort vehicles by radio_call_sign by default
                        usort($vehicles, function($a, $b) {
                            return strcasecmp($a['radio_call_sign'] ?? '', $b['radio_call_sign'] ?? '');
                        });
                        foreach ($vehicles as $vehicle): 
                            $locationName = DataStore::getLocationNameById($vehicle['location_id'] ?? null) ?? $vehicle['location'] ?? '-';
                        ?>
                        <tr data-type="<?php echo htmlspecialchars($vehicle['type']); ?>" 
                            data-location="<?php echo htmlspecialchars($locationName); ?>"
                            data-radio="<?php echo htmlspecialchars($vehicle['radio_call_sign'] ?? ''); ?>">
                            <td><strong><?php echo htmlspecialchars($vehicle['type']); ?></strong></td>
                            <td><?php echo htmlspecialchars($locationName); ?></td>
                            <td><?php echo htmlspecialchars($vehicle['radio_call_sign'] ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Filter and search functionality
let currentSortColumn = 'radio_call_sign';
let currentSortDirection = 'asc';

document.getElementById('searchVehicles')?.addEventListener('input', filterVehicles);
document.getElementById('filterLocation')?.addEventListener('change', filterVehicles);
document.getElementById('filterType')?.addEventListener('change', filterVehicles);

function filterVehicles() {
    const searchTerm = document.getElementById('searchVehicles')?.value.toLowerCase() || '';
    const locationFilter = document.getElementById('filterLocation')?.value.toLowerCase() || '';
    const typeFilter = document.getElementById('filterType')?.value.toLowerCase() || '';
    
    const rows = document.querySelectorAll('#vehiclesTableBody tr');
    
    rows.forEach(row => {
        const type = row.dataset.type?.toLowerCase() || '';
        const location = row.dataset.location?.toLowerCase() || '';
        const radio = row.dataset.radio?.toLowerCase() || '';
        
        const matchesSearch = !searchTerm || type.includes(searchTerm) || radio.includes(searchTerm);
        const matchesLocation = !locationFilter || location === locationFilter;
        const matchesType = !typeFilter || type === typeFilter;
        
        if (matchesSearch && matchesLocation && matchesType) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function sortVehicles(column) {
    const tbody = document.getElementById('vehiclesTableBody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    // Toggle sort direction
    if (currentSortColumn === column) {
        currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        currentSortColumn = column;
        currentSortDirection = 'asc';
    }
    
    rows.sort((a, b) => {
        let aVal = '';
        let bVal = '';
        
        if (column === 'type') {
            aVal = a.dataset.type || '';
            bVal = b.dataset.type || '';
        } else if (column === 'location') {
            aVal = a.dataset.location || '';
            bVal = b.dataset.location || '';
        } else if (column === 'radio_call_sign') {
            aVal = a.dataset.radio || '';
            bVal = b.dataset.radio || '';
        }
        
        const comparison = aVal.localeCompare(bVal, 'de', { sensitivity: 'base' });
        return currentSortDirection === 'asc' ? comparison : -comparison;
    });
    
    // Re-append rows in sorted order
    rows.forEach(row => tbody.appendChild(row));
}
</script>
