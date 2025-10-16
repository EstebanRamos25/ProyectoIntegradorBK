# testing-scripts/locustfile.py

from locust import HttpUser, task, between
from bs4 import BeautifulSoup

class WebsiteUser(HttpUser):
    wait_time = between(1, 3)

    def on_start(self):
        """
        Se ejecuta al iniciar cada usuario de Locust.
        Simula login para obtener la cookie de sesión.
        """
        # Paso 1: obtener la página de login para extraer token CSRF si es necesario
        resp = self.client.get("/admin/login", name="Login Page")
        # Si Orchid/Laravel Orchid utiliza CSRF (generalmente sí), extraemos el token:
        # Nota: si tu login no requiere token o Locust maneja cookies automáticamente, omite esta parte.
        try:
            soup = BeautifulSoup(resp.text, "html.parser")
            token_input = soup.find("input", {"name": "_token"})
            token = token_input["value"] if token_input else None
        except Exception:
            token = None

        payload = {
            "email": "admin@gmail.com",
            "password": "admin123",
        }
        if token:
            payload["_token"] = token

        # Enviar POST de login. Laravel normalmente redirige con 302.
        response = self.client.post("/admin/login", data=payload, name="Login")
        # Opcional: verificar si login fue exitoso vía status o contenido
        if response.status_code not in (200, 302):
            print(f"[Locust] Login falló: status_code={response.status_code}")

    @task
    def view_dashboard(self):
        """
        Tras login, visita el dashboard para validar acceso.
        """
        self.client.get("/admin/main", name="Dashboard")

    # Si quieres más tareas tras login, agrégalas, p.ej. listar recursos:
    # @task
    # def list_products(self):
    #     self.client.get("/admin/crud/list/producto-resources", name="List Products")
