from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
import pymysql

app = FastAPI()

# Habilitar CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost"],  # Permite solicitudes desde localhost (frontend de Moodle)
    allow_credentials=True,
    allow_methods=["*"],  # Permite todos los métodos (GET, POST, etc.)
    allow_headers=["*"],  # Permite todos los encabezados
)

# Conexión a la base de datos
def get_db_connection():
    return pymysql.connect(
        host="localhost",         # Dirección del servidor
        user="root",              # Usuario de la base de datos
        password="",              # Contraseña del usuario (déjalo vacío si no tienes contraseña)
        database="moodle_tic",    # Nombre de la base de datos
        port=3306,                # Puerto estándar de MySQL/MariaDB
        cursorclass=pymysql.cursors.DictCursor  # Resultados como diccionarios
    )

# Endpoint para verificar usuarios
@app.get("/users")
def get_users():
    connection = get_db_connection()
    with connection.cursor() as cursor:
        cursor.execute("SELECT id, firstname, lastname FROM mdl_user LIMIT 5")  # Consulta de prueba
        users = cursor.fetchall()
    connection.close()
    return users

# Endpoint raíz para probar el servidor
@app.get("/")
def root():
    return {"message": "FastAPI está funcionando correctamente"}
