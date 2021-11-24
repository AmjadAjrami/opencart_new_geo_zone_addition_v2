<?php

class ModelLocalisationGeoZone extends Model
{
    public function addGeoZone($data)
    {
        $this->db->query("INSERT INTO " . DB_PREFIX . "geo_zone SET name = '" . $this->db->escape($data['name']) . "', description = '" . $this->db->escape($data['description']) . "', date_added = NOW()");

        $geo_zone_id = $this->db->getLastId();

        if (isset($data['zone_to_geo_zone'])) {
            foreach ($data['zone_to_geo_zone'] as $zone) {
                foreach ($zone['zone_id'] as $value) {
                    $this->db->query("DELETE FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$geo_zone_id . "' AND country_id = '" . (int)$zone['country_id'] . "' AND zone_id = '" . (int)$value . "'");

                    $this->db->query("INSERT INTO " . DB_PREFIX . "zone_to_geo_zone SET country_id = '" . (int)$zone['country_id'] . "', zone_id = '" . (int)$value . "', geo_zone_id = '" . (int)$geo_zone_id . "', date_added = NOW()");
                }
            }
        }

        $this->cache->delete('geo_zone');

        return $geo_zone_id;
    }

    public function editGeoZone($geo_zone_id, $data)
    {
        $this->db->query("UPDATE " . DB_PREFIX . "geo_zone SET name = '" . $this->db->escape($data['name']) . "', description = '" . $this->db->escape($data['description']) . "', date_modified = NOW() WHERE geo_zone_id = '" . (int)$geo_zone_id . "'");

        $this->db->query("DELETE FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$geo_zone_id . "'");

        if (isset($data['zone_to_geo_zone'])) {
            foreach ($data['zone_to_geo_zone'] as $zone) {
                foreach ($zone['zone_id'] as $value) {
                    $this->db->query("DELETE FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$geo_zone_id . "' AND country_id = '" . (int)$zone['country_id'] . "' AND zone_id = '" . (int)$value . "'");

                    $this->db->query("INSERT INTO " . DB_PREFIX . "zone_to_geo_zone SET country_id = '" . (int)$zone['country_id'] . "', zone_id = '" . (int)$value . "', geo_zone_id = '" . (int)$geo_zone_id . "', date_added = NOW()");
                }
            }
        }

        $this->cache->delete('geo_zone');
    }

    public function deleteGeoZone($geo_zone_id)
    {
        $this->db->query("DELETE FROM " . DB_PREFIX . "geo_zone WHERE geo_zone_id = '" . (int)$geo_zone_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$geo_zone_id . "'");

        $this->cache->delete('geo_zone');
    }

    public function getGeoZone($geo_zone_id)
    {
        $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "geo_zone WHERE geo_zone_id = '" . (int)$geo_zone_id . "'");

        return $query->row;
    }

    public function getGeoZones($data = array())
    {
        if ($data) {
            $sql = "SELECT * FROM " . DB_PREFIX . "geo_zone";

            $sort_data = array(
                'name',
                'description'
            );

            if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
                $sql .= " ORDER BY " . $data['sort'];
            } else {
                $sql .= " ORDER BY name";
            }

            if (isset($data['order']) && ($data['order'] == 'DESC')) {
                $sql .= " DESC";
            } else {
                $sql .= " ASC";
            }

            if (isset($data['start']) || isset($data['limit'])) {
                if ($data['start'] < 0) {
                    $data['start'] = 0;
                }

                if ($data['limit'] < 1) {
                    $data['limit'] = 20;
                }

                $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
            }

            $query = $this->db->query($sql);

            return $query->rows;
        } else {
            $geo_zone_data = $this->cache->get('geo_zone');

            if (!$geo_zone_data) {
                $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "geo_zone ORDER BY name ASC");

                $geo_zone_data = $query->rows;

                $this->cache->set('geo_zone', $geo_zone_data);
            }

            return $geo_zone_data;
        }
    }

    public function getTotalGeoZones()
    {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "geo_zone");

        return $query->row['total'];
    }

    public function getZoneToGeoZones($geo_zone_id)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$geo_zone_id . "' GROUP BY country_id");

        $result = $query->rows;
        $items = [];
        foreach ($result as $item) {
            $zone_name = $this->db->query("SELECT name FROM " . DB_PREFIX . "zone WHERE zone_id = '" . (int)$item['zone_id'] . "'");
            $zones = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE country_id = '" . (int)$item['country_id'] . "'");

            $all_rows = [];
            foreach ($zones->rows as $row) {
                $zone_name_2 = $this->db->query("SELECT name FROM " . DB_PREFIX . "zone WHERE zone_id = '" . (int)$row['zone_id'] . "'");
                $all_rows[] = [
                    'zone_to_geo_zone_id' => $row['zone_to_geo_zone_id'],
                    'country_id' => $row['country_id'],
                    'zone_id' => $row['zone_id'],
                    'zone_name' => $zone_name_2->row['name'],
                    'geo_zone_id' => $row['geo_zone_id'],
                    'date_added' => $row['date_added'],
                    'date_modified' => $row['date_modified'],
                ];
            }

            $items[] = [
                'zone_to_geo_zone_id' => $item['zone_to_geo_zone_id'],
                'country_id' => $item['country_id'],
                'zone_id' => $item['zone_id'],
                'zone_name' => $zone_name->row['name'],
                'geo_zone_id' => $item['geo_zone_id'],
                'date_added' => $item['date_added'],
                'date_modified' => $item['date_modified'],
                'zones' => $all_rows,
            ];
        }
//
        return $items;
    }

    public function getTotalZoneToGeoZoneByGeoZoneId($geo_zone_id)
    {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$geo_zone_id . "'");

        return $query->row['total'];
    }

    public function getTotalZoneToGeoZoneByCountryId($country_id)
    {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "zone_to_geo_zone WHERE country_id = '" . (int)$country_id . "'");

        return $query->row['total'];
    }

    public function getZoneNameByZoneId($zone_id)
    {
        $query = $this->db->query("SELECT name FROM " . DB_PREFIX . "zone WHERE zone_id = '" . (int)$zone_id . "'");

        return $query->row;
    }

    public function getTotalZoneToGeoZoneByZoneId($zone_id)
    {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "zone_to_geo_zone WHERE zone_id = '" . (int)$zone_id . "'");

        return $query->row['total'];
    }
}