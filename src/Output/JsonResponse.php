<?php 

declare(strict_types=1);

namespace Selvi\Output;

class JsonResponse extends Response {

    private ?array $jsonData;
    private int $jsonOptions;

    function __construct(?array $data = null, int $code = 200, int $options = JSON_PRETTY_PRINT) {
        $this->jsonData = $data;
        $this->jsonOptions = $options;
        $this->setCode($code);
    }

    public function getData(): ?array {
        return $this->jsonData;
    }

    public function getOptions(): int {
        return $this->jsonOptions;
    }

    public function setData(array $data): void {
        $this->jsonData = $data;
    }

    public function setOptions(int $options): void {
        $this->jsonOptions = $options;
    }

    #[\Override]
    public function send(): void {
        $this->setContent($this->jsonData !== null ? json_encode($this->jsonData, $this->jsonOptions) : null);
        parent::send();
    }

}