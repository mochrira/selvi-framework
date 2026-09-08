# Selvi Framework
⚡ Super fast PHP Framework for building API

## Update plan for 3.x

`Model.php`

```
namespace Selvi;

class Model {

  static function all() {
    // return object Collection in the database
  }

  static function find(mixed $id) {
    // return single object in the database by id
  }

  function create() {

  }

  function update() {

  }

  function delete() {

  }

}
```

`Kontak.php`

```
<?php 

namespace App\Models;

use Selvi\Database\Model;
use Selvi\Database\Attributes\Schema;
use Selvi\Database\Attributes\Table;
use Selvi\Database\Attributes\Column;
use Selvi\Database\Attributes\Key;

#[Schema('main')]
#[Table('kontak')]
class Kontak extends Model {

  #[Key(increment: true)]
  #[Column('idKontak')]
  public int $idKontak;

  #[Column('nmKontak')]
  public string $nmKontak;

}

```

`KontakController.php`

```
<?php 

namespace App\Controllers;

use Selvi\Controller;

class KontakController extends Controller {

  function result() {
    
    $data = Kontak::all();
  }

  function row(int $idKontak) {
    $row = Kontak::find($idKontak);
  }

  function insert() {
    $kontak = new Kontak();
    $kontak->nmKontak = "Moch. Rizal Rachmadani";
    $kontak->create();
  }

  function update(int $idKontak) {
    $kontak = Kontak::find($idKontak);
    $kontak->nmKontak = "Moch. Yusuf Rizal";
    $kontak->update();
  }

  function delete() {
    $kontak = Kontak::find($idKontak);
    $kontak->delete();
  }

}
```