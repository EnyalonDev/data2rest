import requests
import json

class Data2RestException(Exception):
    pass

class Data2RestClient:
    """
    Official Python Client for Data2Rest API
    """
    def __init__(self, base_url, api_key, version='v2'):
        self.base_url = base_url.rstrip('/')
        self.api_key = api_key
        self.version = version
        self.session = requests.Session()
        
        self.session.headers.update({
            'X-API-KEY': self.api_key,
            'Content-Type': 'application/json'
        })
        
        if self.version == 'v2':
            self.session.headers.update({
                'Accept': 'application/vnd.data2rest.v2+json'
            })

    def database(self, db_id):
        return DatabaseResource(self, db_id)

class DatabaseResource:
    def __init__(self, client, db_id):
        self.client = client
        self.db_id = db_id

    def table(self, table_name):
        return TableResource(self.client, self.db_id, table_name)

class TableResource:
    def __init__(self, client, db_id, table_name):
        self.client = client
        self.endpoint = f"{client.base_url}/db/{db_id}/{table_name}"

    def get(self, **params):
        """
        Get records with optional filtering
        usage: .get(limit=10, sort='-id', name__like='Test%')
        """
        response = self.client.session.get(self.endpoint, params=params)
        return self._handle(response)

    def find(self, record_id):
        """Get single record"""
        url = f"{self.endpoint}/{record_id}"
        response = self.client.session.get(url)
        return self._handle(response)

    def create(self, data):
        """Create new record"""
        response = self.client.session.post(self.endpoint, json=data)
        return self._handle(response)

    def update(self, record_id, data):
        """Update record"""
        url = f"{self.endpoint}/{record_id}"
        response = self.client.session.put(url, json=data)
        return self._handle(response)

    def delete(self, record_id):
        """Delete record"""
        url = f"{self.endpoint}/{record_id}"
        response = self.client.session.delete(url)
        return self._handle(response)

    def bulk(self, operations):
        """Execute bulk operations"""
        url = f"{self.endpoint}/bulk"
        payload = {"operations": operations}
        response = self.client.session.post(url, json=payload)
        return self._handle(response)

    def _handle(self, response):
        try:
            data = response.json()
        except:
            data = {"error": response.text}

        if not response.ok:
            raise Data2RestException(f"[{response.status_code}] {data.get('error', 'Unknown Error')}")
        
        return data
