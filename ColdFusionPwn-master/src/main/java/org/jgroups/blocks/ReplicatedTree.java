package org.jgroups.blocks;

import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.ObjectOutputStream;

public class ReplicatedTree {

    private Object payload;
    private byte[] state;

    public ReplicatedTree(Object payload){
        this.payload = payload;
    }

    public byte[] getState() throws IOException {

        ByteArrayOutputStream stream = new ByteArrayOutputStream();
        stream.write(2);

        ObjectOutputStream oos = new ObjectOutputStream(stream);
        oos.writeObject(payload);
        state = stream.toByteArray();

        return state;
    }

    public void setState(byte[] state) {
        //we don't care
    }
}