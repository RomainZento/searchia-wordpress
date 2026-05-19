FROM python:3.11-slim

WORKDIR /app

# Installation des dépendances système pour mysqlclient si besoin
RUN apt-get update && apt-get install -y default-libmysqlclient-dev build-essential && rm -rf /var/lib/apt/lists/*

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8000"]