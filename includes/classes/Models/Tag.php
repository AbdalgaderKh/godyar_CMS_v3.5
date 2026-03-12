<?php

namespace Godyar\Models;

class Tag extends BaseModel {

  
  public function all(): array {
    if (class_exists('\Cache') === TRUE) {
      return \Cache::remember('tags_all_v1', 3600, function () {
        return $this->db->query('SELECT * FROM tags ORDER BY name')->fetchAll();
      });
    }
    return $this->db->query('SELECT * FROM tags ORDER BY name')->fetchAll();
  }

  public function create(string $name, string $slug): bool {
    $st = $this->db->prepare('INSERT INTO tags(name,slug) VALUES(:n,:s)');
    $ok = $st->execute([':n' => $name, ':s' => $slug]);
    if (($ok === TRUE) && (class_exists('\Cache') === TRUE)) {
      \Cache::forget('tags_all_v1');
    }
    return $ok;
  }

  public function delete(int $id): bool {
    $st = $this->db->prepare('DELETE FROM tags WHERE id=:id');
    $ok = $st->execute([':id' => $id]);
    if (($ok === TRUE) && (class_exists('\Cache') === TRUE)) {
      \Cache::forget('tags_all_v1');
    }
    return $ok;
  }
}
