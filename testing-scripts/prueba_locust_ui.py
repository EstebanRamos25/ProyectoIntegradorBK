# Modificar para generar reporte HTML
from locust import HttpUser, task, between
from locust.env import Environment
from locust.log import setup_logging
from locust.stats import stats_printer, stats_history
import gevent
import time
import sys
# En locustfile.py
class WebsiteUser(HttpUser):
    client = None
    
    def on_start(self):
        self.client = Client(
            base_url=self.host,
            headers={"Content-Type": "application/json"},
            verify=False,
            timeout=10
        )
def run_locust_test(host, users, spawn_rate, duration, report_path):
    setup_logging("INFO", None)
    
    class QuickstartUser(HttpUser):
        wait_time = between(1, 5)
        
        @task
        def index_page(self):
            self.client.get("/", name="Home")
            
        @task(3)
        def view_items(self):
            for item_id in range(10):
                self.client.get(f"/item?id={item_id}", name="Item")
                time.sleep(1)
    
    env = Environment(user_classes=[QuickstartUser])
    env.create_local_runner()
    
    # Iniciar recolección de stats
    gevent.spawn(stats_printer(env.stats))
    gevent.spawn(stats_history, env.runner)
    
    # Iniciar prueba
    env.runner.start(users, spawn_rate=spawn_rate)
    gevent.spawn_later(duration, lambda: env.runner.quit())
    env.runner.greenlet.join()
    
    # Generar reporte HTML
    with open(report_path, "w") as f:
        f.write("<html><body><h1>Locust Report</h1>")
        f.write(f"<p>Users: {users}</p>")
        f.write(f"<p>Duration: {duration}s</p>")
        f.write("<table border='1'><tr><th>Endpoint</th><th>Requests</th><th>Failures</th></tr>")
        
        for key, stats in env.stats.entries.items():
            if stats.num_requests > 0:
                f.write(f"<tr><td>{stats.name}</td><td>{stats.num_requests}</td><td>{stats.num_failures}</td></tr>")
        
        f.write("</table></body></html>")

if __name__ == "__main__":
    # Configuración desde argumentos
    run_locust_test(
        host="http://localhost",
        users=100,
        spawn_rate=10,
        duration=60,
        report_path=sys.argv[1] if len(sys.argv) > 1 else "report.html"
    )