<?php 

namespace Selvi\Tests\Controllers;

use DateTime;
use Selvi\Database\Builder\DML\WithBuilder;
use Selvi\Database\DB;
use Selvi\Database\Interfaces\IQueryBuilder;
use Selvi\Exception;
use Selvi\Input\Request;
use Selvi\Output\JsonResponse;
use Selvi\Tests\Models\Kontak;
use Selvi\Tests\Services\KontakService;

class KontakController {

    function __construct(
        private Request $request,
        private KontakService $kontakService
    ) { }

    function resultModel() {
        $baseQuery = Kontak::with('departemen', fn($b) => $b
            ->with('jmlKontak'))
        ->with('unit', fn($b) => $b
            ->with('departemen'))
        ->with('profesi', fn($b) => $b
            ->with('unit', fn($b) => $b
                ->with('departemen')
        ));

        $count = (clone $baseQuery)->count();
        $data = (clone $baseQuery)->all();

        return jsonResponse([
            'count' => $count,
            'data' => $data->toArray()
        ]);
    }

    function resultService() {
        /** @var KontakService */
        $kontakService = inject(KontakService::class);
        $data = $kontakService->result();
        return jsonResponse([
            'data' => $data->toArray()
        ]);
    }

    function result(string $metode) {
        switch($metode) {
            case 'model': return $this->resultModel(); break;
            default: return $this->resultService(); break;
        }
    }

    function rowModel(int $idKontak) {
        $kontak = Kontak::with('departemen', fn($b) => $b
                ->with('jmlKontak'))
            ->with('unit', fn($b) => $b
                ->with('departemen', fn($b) => $b
                    ->with('jmlKontak')))
            ->with('profesi', fn($b) => $b
                ->with('unit', fn($b) => $b
                    ->with('departemen', fn($b) => $b
                        ->with('jmlKontak'))
            ))->find($idKontak);
        if($kontak == null)
            throw new Exception("Kontak tidak ditemukan", "kontak/not-found", 404);
        return jsonResponse($kontak->toArray());
    }

    function rowService(int $idKontak) {
        $kontak = $this->kontakService->row([['kontak.id_kontak', $idKontak]]);
        return jsonResponse($kontak->toArray());
    }    

    function row(string $metode, string $idKontak) {
        switch($metode) {
            case 'model': return $this->rowModel((int)$idKontak); break;
            default: return $this->rowService((int)$idKontak); break;
        }
    }

    function insertModel() {
        $data = json_decode($this->request->raw() ?? '', true) ?? [];
        $kontak = Kontak::create([
            'nm_kontak' => $data['nmKontak'],
            'id_departemen' => $data['idDepartemen'],
            'id_unit' => $data['idUnit'],
            'id_profesi' => $data['idProfesi'],
            'created_at' => new DateTime()
        ]);
        return jsonResponse([
            'idKontak' => $kontak->idKontak
        ]);
    }

    function insertService() {
        return jsonResponse(null);
    }

    function insert(string $metode) {
        switch($metode) {
            case 'model': return $this->insertModel(); break;
            default: return $this->insertService(); break;
        }
    }

    function updateModel(int $idKontak) {
        $kontak = Kontak::find($idKontak);
        if($kontak == null)
            throw new Exception("Kontak tidak ditemukan", "kontak/not-found", 404);
        $data = json_decode($this->request->raw() ?? '', true) ?? [];
        $kontak->nmKontak = $data['nmKontak'];
        $kontak->idDepartemen = $data['idDepartemen'];
        $kontak->idUnit = $data['idUnit'];
        $kontak->idProfesi = $data['idProfesi'];
        $kontak->createdAt = new DateTime();
        $kontak->update();
        return jsonResponse(null, 204);
    }

    function updateService(int $idKontak) {
        return jsonResponse(null);
    }

    function update(string $metode, string $idKontak) {
        switch($metode) {
            case 'model': return $this->updateModel((int)$idKontak); break;
            default: return $this->updateService((int)$idKontak); break;
        }
        return \jsonResponse(null, 204);
    }

    function deleteModel(int $idKontak) {
        $kontak = Kontak::find($idKontak);
        if($kontak == null)
            throw new Exception("Kontak tidak ditemukan", "kontak/not-found", 404);
        $kontak->delete();
        return jsonResponse(null, 204);
    }

    function deleteService(int $idKontak) {
        return jsonResponse(null, 204);
    }

    function delete(string $metode, string $idKontak) {
        switch($metode) {
            case 'model': return $this->deleteModel((int)$idKontak); break;
            default: return $this->deleteService((int)$idKontak); break;
        }
    }

}