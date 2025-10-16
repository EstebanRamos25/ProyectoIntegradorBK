#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from datetime import datetime
import os

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
REPORT_PATH = os.path.join(BASE_DIR, "reports", "smoke_report.html")

def do_login(driver, base_url, email, password, timeout=10):
    login_url = f"{base_url}/admin/login"
    driver.get(login_url)
    WebDriverWait(driver, timeout).until(
        EC.presence_of_element_located((By.NAME, "email"))
    )
    driver.find_element(By.NAME, "email").send_keys(email)
    driver.find_element(By.NAME, "password").send_keys(password)
    try:
        driver.find_element(By.ID, "button-login").click()
    except:
        driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
    WebDriverWait(driver, timeout).until(
        EC.url_contains("/admin/main")
    )

def run_smoke_tests():
    chrome_options = Options()
    chrome_options.add_argument("--headless")
    driver = webdriver.Chrome(options=chrome_options)

    base_url = "http://127.0.0.1:8000"
    email = "admin@gmail.com"
    password = "admin123"

    results = []
    start_time = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

    try:
        do_login(driver, base_url, email, password)
    except Exception as e:
        with open(REPORT_PATH, "w", encoding="utf-8") as f:
            f.write(f"""
            <html><head><meta charset="UTF-8"><title>Smoke Tests - Login FAILED</title></head>
            <body><h1>Smoke Tests - Login FAILED</h1><p>{e}</p></body></html>
            """)
        driver.quit()
        print(f"Login failed: {e}. Reporte en {REPORT_PATH}")
        return

    urls = [
        f"{base_url}/admin/main",
        f"{base_url}/admin/crud/list/producto-resources",
        # Agrega más URLs según sea necesario
    ]

    for url in urls:
        try:
            driver.get(url)
            WebDriverWait(driver, 5).until(
                EC.presence_of_element_located((By.TAG_NAME, "body"))
            )
            title = driver.title or ""
            if "Error" in title or title.strip() == "":
                results.append((url, "FAIL", f"Título inesperado: {title}"))
            else:
                results.append((url, "OK", title))
        except Exception as e:
            results.append((url, "ERROR", str(e)))

    driver.quit()

    # Generar HTML estilizado
    with open(REPORT_PATH, "w", encoding="utf-8") as f:
        f.write(f"""
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Pruebas de Humo</title>
    <style>
        body {{
            font-family: Arial, sans-serif;
            margin: 40px;
            background-color: #f8f9fa;
        }}
        h1 {{
            color: #333;
        }}
        table {{
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }}
        th, td {{
            padding: 12px;
            border: 1px solid #dee2e6;
            text-align: left;
        }}
        th {{
            background-color: #343a40;
            color: #fff;
        }}
        tr:nth-child(even) {{
            background-color: #f1f1f1;
        }}
        .ok {{ background-color: #d4edda; color: #155724; }}
        .fail {{ background-color: #f8d7da; color: #721c24; }}
        .error {{ background-color: #fff3cd; color: #856404; }}
        footer {{
            margin-top: 40px;
            font-size: 0.9em;
            color: #555;
        }}
    </style>
</head>
<body>
    <h1>Reporte de Pruebas de Humo</h1>
    <p>Fecha y hora de generación: <strong>{start_time}</strong></p>
    <table>
        <tr>
            <th>URL</th>
            <th>Estado</th>
            <th>Detalles</th>
        </tr>
""")
        for url, estado, detalle in results:
            clase = "ok" if estado == "OK" else "fail" if estado == "FAIL" else "error"
            f.write(f"<tr class='{clase}'><td>{url}</td><td>{estado}</td><td>{detalle}</td></tr>")

        f.write(f"""
    </table>
    <footer>
        <p>Generado automáticamente por el sistema de pruebas de humo.</p>
    </footer>
</body>
</html>
""")

    print(f"Smoke tests finalizados. Reporte en: {REPORT_PATH}")

if __name__ == "__main__":
    run_smoke_tests()
