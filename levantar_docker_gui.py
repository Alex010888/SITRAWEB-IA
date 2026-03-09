# -*- coding: utf-8 -*-
"""
GUI para levantar el proyecto SITRACABAÑA con Docker Desktop.
Requiere: Docker Desktop instalado y en ejecución.
Ejecutar desde la raíz del proyecto: python levantar_docker_gui.py
"""
import os
import subprocess
import sys
import tkinter as tk
from tkinter import ttk, scrolledtext, messagebox
from pathlib import Path

# Raíz del proyecto (donde está docker-compose.yml)
PROJECT_ROOT = Path(__file__).resolve().parent
COMPOSE_FILE = PROJECT_ROOT / "docker-compose.yml"


def run_cmd(cmd: list, cwd: Path) -> tuple[str, int]:
    """Ejecuta comando y devuelve (salida, código)."""
    use_shell = sys.platform == "win32"
    if use_shell:
        cmd = " ".join(f'"{c}"' if " " in str(c) else str(c) for c in cmd)
    try:
        r = subprocess.run(
            cmd,
            cwd=cwd,
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace",
            shell=use_shell,
        )
        out = (r.stdout or "") + (r.stderr or "")
        return out.strip(), r.returncode
    except Exception as e:
        return str(e), -1


def docker_available() -> bool:
    out, code = run_cmd(["docker", "info"], PROJECT_ROOT)
    return code == 0


def compose_up(log_widget: scrolledtext.ScrolledText) -> None:
    if not COMPOSE_FILE.exists():
        messagebox.showerror("Error", f"No se encontró {COMPOSE_FILE}")
        return
    log_widget.delete("1.0", tk.END)
    log_widget.insert(tk.END, "Levantando servicios (docker-compose up -d)...\n")
    log_widget.update()
    out, code = run_cmd(
        ["docker", "compose", "up", "-d", "--build"],
        PROJECT_ROOT,
    )
    log_widget.insert(tk.END, out or "(sin salida)")
    if code != 0:
        log_widget.insert(tk.END, f"\n\nCódigo de salida: {code}")
    else:
        log_widget.insert(
            tk.END,
            "\n\n--- Listo. URLs:\n"
            "  • Sitio PHP:  http://localhost:8080\n"
            "  • Frontend:  http://localhost:3000\n"
            "  • Defensor:  http://localhost:5000\n",
        )
    log_widget.see(tk.END)


def compose_down(log_widget: scrolledtext.ScrolledText) -> None:
    if not COMPOSE_FILE.exists():
        messagebox.showerror("Error", f"No se encontró {COMPOSE_FILE}")
        return
    log_widget.delete("1.0", tk.END)
    log_widget.insert(tk.END, "Deteniendo servicios (docker-compose down)...\n")
    log_widget.update()
    out, code = run_cmd(["docker", "compose", "down"], PROJECT_ROOT)
    log_widget.insert(tk.END, out or "(sin salida)")
    if code != 0:
        log_widget.insert(tk.END, f"\n\nCódigo de salida: {code}")
    log_widget.see(tk.END)


def compose_status(log_widget: scrolledtext.ScrolledText) -> None:
    log_widget.delete("1.0", tk.END)
    log_widget.insert(tk.END, "Estado de contenedores (docker compose ps):\n\n")
    log_widget.update()
    out, code = run_cmd(["docker", "compose", "ps"], PROJECT_ROOT)
    log_widget.insert(tk.END, out or "(sin salida)")
    log_widget.see(tk.END)


def main():
    root = tk.Tk()
    root.title("SITRACABAÑA - Levantar proyecto (Docker)")
    root.minsize(520, 380)
    root.geometry("600x420")

    # Comprobar Docker
    if not docker_available():
        messagebox.showwarning(
            "Docker",
            "Docker no está en ejecución o no está instalado.\n"
            "Abre Docker Desktop y vuelve a ejecutar esta ventana.",
        )

    # Frame de botones
    btn_frame = ttk.Frame(root, padding=10)
    btn_frame.pack(fill=tk.X)

    # Área de log
    log_frame = ttk.LabelFrame(root, text="Salida", padding=5)
    log_frame.pack(fill=tk.BOTH, expand=True, padx=10, pady=(0, 10))
    log = scrolledtext.ScrolledText(log_frame, height=14, wrap=tk.WORD, font=("Consolas", 9))
    log.pack(fill=tk.BOTH, expand=True)

    ttk.Button(btn_frame, text="Levantar proyecto", command=lambda: compose_up(log)).pack(side=tk.LEFT, padx=3)
    ttk.Button(btn_frame, text="Detener proyecto", command=lambda: compose_down(log)).pack(side=tk.LEFT, padx=3)
    ttk.Button(btn_frame, text="Ver estado", command=lambda: compose_status(log)).pack(side=tk.LEFT, padx=3)

    # URLs
    url_frame = ttk.Frame(root, padding=10)
    url_frame.pack(fill=tk.X)
    ttk.Label(url_frame, text="Sitio PHP: http://localhost:8080  |  Frontend: http://localhost:3000  |  Defensor: http://localhost:5000", font=("Segoe UI", 9)).pack(anchor=tk.W)

    root.mainloop()


if __name__ == "__main__":
    main()
