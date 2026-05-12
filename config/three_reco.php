<?php

return [
    // Path relativo dentro del disk "local" (storage/app)
    'model_path' => env('THREE_RECO_MODEL_PATH', 'three-reco/model.json'),

    // Dataset generado por el comando de entrenamiento
    'dataset_path' => env('THREE_RECO_DATASET_PATH', 'three-reco/dataset.json'),

    // Tamaños por defecto del MLP (se guardan también en model.json)
    'vector_size' => (int) env('THREE_RECO_VECTOR_SIZE', 128),
    'hidden_size' => (int) env('THREE_RECO_HIDDEN_SIZE', 64),
];
