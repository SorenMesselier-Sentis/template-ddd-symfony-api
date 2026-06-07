FROM minio/minio:latest

COPY --from=minio/mc:latest /usr/bin/mc /usr/bin/mc
COPY docker-entrypoint.sh /usr/local/bin/minio-docker-entrypoint.sh
RUN chmod +x /usr/local/bin/minio-docker-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/minio-docker-entrypoint.sh"]
