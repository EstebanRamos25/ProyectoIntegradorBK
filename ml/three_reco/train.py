import argparse
import json
import math
import os
import random
from typing import List, Dict, Any


def relu(x: float) -> float:
    return x if x > 0.0 else 0.0


def softmax(logits: List[float]) -> List[float]:
    if not logits:
        return []
    m = max(logits)
    exps = [math.exp(v - m) for v in logits]
    s = sum(exps)
    if s <= 0.0:
        return [1.0 / len(logits) for _ in logits]
    return [e / s for e in exps]


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--dataset", required=True)
    ap.add_argument("--out", required=True)
    ap.add_argument("--epochs", type=int, default=25)
    ap.add_argument("--lr", type=float, default=0.05)
    ap.add_argument("--seed", type=int, default=13)
    args = ap.parse_args()

    random.seed(args.seed)

    with open(args.dataset, "r", encoding="utf-8") as f:
        ds = json.load(f)

    vector_size = int(ds.get("vector_size", 0))
    hidden_size = int(ds.get("hidden_size", 0))
    samples = ds.get("samples", [])

    if vector_size <= 0 or hidden_size <= 0:
        raise SystemExit("dataset inválido (vector/hidden)")

    # classes = categorías observadas
    cats = sorted({int(s.get("y_cat", 0)) for s in samples if int(s.get("y_cat", 0)) > 0})
    if not cats:
        # modelo vacío pero válido
        model = {
            "version": 1,
            "vector_size": vector_size,
            "hidden_size": hidden_size,
            "classes": [],
            "W1": [],
            "b1": [],
            "W2": [],
            "b2": [],
        }
        os.makedirs(os.path.dirname(args.out), exist_ok=True)
        with open(args.out, "w", encoding="utf-8") as f:
            json.dump(model, f)
        print("Reco train: sin muestras, modelo vacío exportado")
        return 0

    cat_to_idx = {c: i for i, c in enumerate(cats)}
    out_size = len(cats)

    # inicialización pequeña
    def randw() -> float:
        return (random.random() - 0.5) * 0.02

    W1 = [[randw() for _ in range(vector_size)] for _ in range(hidden_size)]
    b1 = [0.0 for _ in range(hidden_size)]
    W2 = [[randw() for _ in range(hidden_size)] for _ in range(out_size)]
    b2 = [0.0 for _ in range(out_size)]

    # entrenamiento
    epochs = max(1, int(args.epochs))
    lr = float(args.lr)
    usable = [s for s in samples if int(s.get("y_cat", 0)) in cat_to_idx]
    if not usable:
        raise SystemExit("No hay muestras usables")

    for ep in range(1, epochs + 1):
        random.shuffle(usable)
        total_loss = 0.0

        for s in usable:
            x_idx = s.get("x_idx", [])
            x_val = s.get("x_val", [])
            y_cat = int(s.get("y_cat", 0))
            y = cat_to_idx[y_cat]

            # forward z1/h
            z1 = [b1j for b1j in b1]
            for idx, val in zip(x_idx, x_val):
                i = int(idx)
                v = float(val)
                if i < 0 or i >= vector_size:
                    continue
                for j in range(hidden_size):
                    z1[j] += W1[j][i] * v

            h = [relu(z) for z in z1]

            logits = [b2k for b2k in b2]
            for k in range(out_size):
                wk = W2[k]
                ssum = logits[k]
                for j in range(hidden_size):
                    ssum += wk[j] * h[j]
                logits[k] = ssum

            probs = softmax(logits)
            py = max(1e-9, probs[y])
            total_loss += -math.log(py)

            # backprop
            dlogits = [p for p in probs]
            dlogits[y] -= 1.0

            # dh = W2^T * dlogits
            dh = [0.0 for _ in range(hidden_size)]
            for k in range(out_size):
                g = dlogits[k]
                wk = W2[k]
                for j in range(hidden_size):
                    dh[j] += wk[j] * g

            # dZ1 con ReLU
            for j in range(hidden_size):
                if z1[j] <= 0.0:
                    dh[j] = 0.0

            # update W2/b2
            for k in range(out_size):
                g = dlogits[k]
                b2[k] -= lr * g
                wk = W2[k]
                for j in range(hidden_size):
                    wk[j] -= lr * (g * h[j])

            # update W1/b1 (sparse)
            for j in range(hidden_size):
                gj = dh[j]
                if gj == 0.0:
                    continue
                b1[j] -= lr * gj
                wj = W1[j]
                for idx, val in zip(x_idx, x_val):
                    i = int(idx)
                    v = float(val)
                    if i < 0 or i >= vector_size:
                        continue
                    wj[i] -= lr * (gj * v)

        avg = total_loss / max(1, len(usable))
        if ep == 1 or ep == epochs or ep % 5 == 0:
            print(f"Reco train: epoch {ep}/{epochs} loss={avg:.4f} samples={len(usable)} classes={out_size}")

    model = {
        "version": 1,
        "vector_size": vector_size,
        "hidden_size": hidden_size,
        "classes": cats,
        "W1": W1,
        "b1": b1,
        "W2": W2,
        "b2": b2,
    }

    os.makedirs(os.path.dirname(args.out), exist_ok=True)
    with open(args.out, "w", encoding="utf-8") as f:
        json.dump(model, f)

    print("Reco train: modelo exportado")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
